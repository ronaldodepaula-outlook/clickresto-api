<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\Assinatura;
use App\Models\ConfirmacaoEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string',
        ]);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['senha'],
        ];

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Credenciais invalidas'], Response::HTTP_UNAUTHORIZED);
        }

        $user = auth('api')->user();
        if ($user && isset($user->ativo) && ! $user->ativo) {
            auth('api')->logout();
            return response()->json(['message' => 'Usuario inativo'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondWithToken($token, $user);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'empresa_id' => 'required|exists:tb_empresas,id',
            'nome' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:tb_usuarios,email',
            'senha' => 'required|string|min:6',
        ]);

        $empresa = Empresa::findOrFail($data['empresa_id']);
        $plano = Plano::find($empresa->plano_id);
        if ($plano && $plano->limite_usuarios > 0) {
            $ativos = Usuario::where('empresa_id', $empresa->id)->where('ativo', true)->count();
            if ($ativos >= $plano->limite_usuarios) {
                return response()->json([
                    'message' => 'Limite de usuarios do plano atingido',
                    'limite_usuarios' => $plano->limite_usuarios,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $user = Usuario::create([
            'empresa_id' => $data['empresa_id'],
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha' => Hash::make($data['senha']),
            'ativo' => true,
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    public function publicCadastro(Request $request)
    {
        $data = $request->validate([
            'plano_id' => 'required|exists:tb_planos,id',
            'empresa.nome' => 'required|string|max:150',
            'empresa.nome_fantasia' => 'nullable|string|max:150',
            'empresa.cnpj' => 'nullable|string|max:20',
            'empresa.telefone' => 'nullable|string|max:20',
            'empresa.email' => 'nullable|email|max:120',
            'empresa.endereco' => 'nullable|string|max:200',
            'empresa.cidade' => 'nullable|string|max:100',
            'empresa.estado' => 'nullable|string|max:50',
            'usuario.nome' => 'required|string|max:150',
            'usuario.email' => 'required|email|max:150|unique:tb_usuarios,email',
            'usuario.senha' => 'required|string|min:6',
            'assinatura.data_inicio' => 'nullable|date',
            'assinatura.data_fim' => 'nullable|date|after_or_equal:assinatura.data_inicio',
        ]);

        $plano = Plano::findOrFail($data['plano_id']);
        if ($plano->limite_usuarios > 0 && $plano->limite_usuarios < 1) {
            return response()->json([
                'message' => 'Plano nao permite criacao de usuarios',
                'limite_usuarios' => $plano->limite_usuarios,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = DB::transaction(function () use ($data, $plano) {
            $empresaData = $data['empresa'];
            $empresaData['plano_id'] = $plano->id;
            $empresaData['status'] = 'ativo';

            $empresa = Empresa::create($empresaData);

            $dataInicio = $data['assinatura']['data_inicio'] ?? now()->toDateString();
            $dataFim = $data['assinatura']['data_fim'] ?? now()->addDays(30)->toDateString();

            $assinatura = Assinatura::create([
                'empresa_id' => $empresa->id,
                'plano_id' => $plano->id,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => 'ativa',
            ]);

            $usuario = Usuario::create([
                'empresa_id' => $empresa->id,
                'nome' => $data['usuario']['nome'],
                'email' => $data['usuario']['email'],
                'senha' => Hash::make($data['usuario']['senha']),
                'ativo' => true,
            ]);

            return [$empresa, $assinatura, $usuario];
        });

        [$empresa, $assinatura, $usuario] = $result;

        $token = auth('api')->login($usuario);

        $emailEnviado = false;
        try {
            Mail::to($usuario->email)->send(new \App\Mail\WelcomeMail($usuario, $empresa, $plano, $assinatura));
            $emailEnviado = true;
        } catch (\Throwable $e) {
            $emailEnviado = false;
        }

        return response()->json([
            'empresa' => $empresa,
            'plano' => $plano,
            'assinatura' => $assinatura,
            'usuario' => $usuario,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'limite_usuarios' => $plano->limite_usuarios,
            'email_enviado' => $emailEnviado,
        ], Response::HTTP_CREATED);
    }

    public function publicCadastroTrial(Request $request)
    {
        $data = $request->validate([
            'empresa.nome' => 'required|string|max:150',
            'empresa.nome_fantasia' => 'nullable|string|max:150',
            'empresa.cnpj' => 'nullable|string|max:20',
            'empresa.telefone' => 'nullable|string|max:20',
            'empresa.email' => 'nullable|email|max:120',
            'empresa.endereco' => 'nullable|string|max:200',
            'empresa.cidade' => 'nullable|string|max:100',
            'empresa.estado' => 'nullable|string|max:50',
            'usuario.nome' => 'required|string|max:150',
            'usuario.email' => 'required|email|max:150|unique:tb_usuarios,email',
            'usuario.senha' => 'required|string|min:6',
        ]);

        $planoId = (int) env('TRIAL_PLAN_ID', 0);
        $plano = $planoId > 0 ? Plano::find($planoId) : null;
        if (!$plano) {
            $plano = Plano::where('ativo', true)->where('valor', 0)->orderBy('id')->first();
        }
        if (!$plano) {
            return response()->json([
                'message' => 'Plano trial nao encontrado. Defina TRIAL_PLAN_ID ou crie um plano ativo com valor 0.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = DB::transaction(function () use ($data, $plano, $request) {
            $empresaData = $data['empresa'];
            $empresaData['plano_id'] = $plano->id;
            $empresaData['status'] = 'suspenso';

            $empresa = Empresa::create($empresaData);

            $assinatura = Assinatura::create([
                'empresa_id' => $empresa->id,
                'plano_id' => $plano->id,
                'data_inicio' => now()->toDateString(),
                'data_fim' => now()->addMonths(3)->toDateString(),
                'status' => 'trial',
            ]);

            $usuario = Usuario::create([
                'empresa_id' => $empresa->id,
                'nome' => $data['usuario']['nome'],
                'email' => $data['usuario']['email'],
                'senha' => Hash::make($data['usuario']['senha']),
                'ativo' => true,
            ]);

            $token = Str::random(64);
            $confirmacao = ConfirmacaoEmail::create([
                'empresa_id' => $empresa->id,
                'usuario_id' => $usuario->id,
                'token' => $token,
                'expira_em' => now()->addHours(48),
            ]);

            $baseUrl = rtrim(config('app.url') ?: $request->getSchemeAndHttpHost(), '/');
            $confirmacaoUrl = $baseUrl . '/api/v1/public/confirmar-email?token=' . $token;

            return [$empresa, $assinatura, $usuario, $confirmacaoUrl, $confirmacao];
        });

        [$empresa, $assinatura, $usuario, $confirmacaoUrl, $confirmacao] = $result;

        $token = auth('api')->login($usuario);

        $emailEnviado = false;
        try {
            Mail::to($usuario->email)->send(new \App\Mail\EmailConfirmacaoMail($usuario, $empresa, $plano, $assinatura, $confirmacaoUrl));
            $emailEnviado = true;
        } catch (\Throwable $e) {
            $emailEnviado = false;
        }

        return response()->json([
            'empresa' => $empresa,
            'plano' => $plano,
            'assinatura' => $assinatura,
            'usuario' => $usuario,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'limite_usuarios' => $plano->limite_usuarios,
            'email_enviado' => $emailEnviado,
            'confirmacao_url' => $confirmacaoUrl,
        ], Response::HTTP_CREATED);
    }

    public function confirmarEmail(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $confirmacao = ConfirmacaoEmail::where('token', $data['token'])->first();
        if (!$confirmacao) {
            return response()->json(['message' => 'Token invalido'], Response::HTTP_NOT_FOUND);
        }

        if ($confirmacao->confirmado_em) {
            return response()->json(['message' => 'E-mail ja confirmado']);
        }

        if (now()->greaterThan($confirmacao->expira_em)) {
            return response()->json(['message' => 'Token expirado'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $confirmacao->confirmado_em = now();
        $confirmacao->save();

        $empresa = Empresa::find($confirmacao->empresa_id);
        if ($empresa) {
            $empresa->status = 'ativo';
            $empresa->save();
        }

        $assinatura = Assinatura::where('empresa_id', $confirmacao->empresa_id)->latest()->first();
        if ($assinatura && $assinatura->status === 'trial') {
            $assinatura->status = 'ativa';
            $assinatura->save();
        }

        return response()->json(['message' => 'E-mail confirmado. Empresa ativada com sucesso.']);
    }

    public function me()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Usuario nao autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json($this->buildUserPayload($user));
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken(string $token, ?Usuario $user = null)
    {
        $response = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];

        if ($user) {
            $response = array_merge($response, $this->buildUserPayload($user));
        }

        return response()->json($response);
    }

    protected function buildUserPayload(Usuario $user): array
    {
        $user->load([
            'empresa.plano',
            'empresa.usuarios',
        ]);

        $empresa = $user->empresa;
        if ($empresa) {
            $assinatura = Assinatura::where('empresa_id', $empresa->id)
                ->orderByDesc('data_fim')
                ->orderByDesc('id')
                ->first();

            if ($assinatura) {
                $licenca = $this->buildLicencaInfo($assinatura);
                $assinatura->setAttribute('licenca', $licenca);
                $empresa->setRelation('assinaturaAtiva', $assinatura);
                $empresa->setAttribute('licenca', $licenca);
            } else {
                $empresa->setRelation('assinaturaAtiva', null);
                $empresa->setAttribute('licenca', $this->buildLicencaInfo(null));
            }
        }

        return $user->toArray();
    }

    protected function buildLicencaInfo(?Assinatura $assinatura): array
    {
        if (! $assinatura || ! $assinatura->data_inicio || ! $assinatura->data_fim) {
            return [
                'status' => 'indisponivel',
                'mensagem' => 'Licenca nao encontrada',
                'dias_restantes' => null,
                'dias_expirados' => null,
                'duracao_dias' => null,
                'duracao_meses' => null,
                'duracao_anos' => null,
            ];
        }

        $inicio = Carbon::parse($assinatura->data_inicio)->startOfDay();
        $fim = Carbon::parse($assinatura->data_fim)->startOfDay();
        $hoje = now()->startOfDay();

        $duracaoDias = $inicio->diffInDays($fim);
        $duracaoMeses = $inicio->diffInMonths($fim);
        $duracaoAnos = $inicio->diffInYears($fim);
        $diasRestantes = $hoje->diffInDays($fim, false);
        $expirada = $diasRestantes < 0;

        return [
            'status' => $expirada ? 'expirada' : 'ok',
            'mensagem' => $expirada ? 'Licenca expirada' : 'Licenca ok',
            'dias_restantes' => $expirada ? 0 : $diasRestantes,
            'dias_expirados' => $expirada ? abs($diasRestantes) : 0,
            'duracao_dias' => $duracaoDias,
            'duracao_meses' => $duracaoMeses,
            'duracao_anos' => $duracaoAnos,
            'data_inicio' => $assinatura->data_inicio,
            'data_fim' => $assinatura->data_fim,
        ];
    }
}
