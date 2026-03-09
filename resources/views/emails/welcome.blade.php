<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <title>Bem-vindo ao ClickResto</title>
  </head>
  <body>
    <p>Ola {{ $usuario->nome }},</p>
    <p>
      Sua conta foi criada com sucesso. A empresa
      <strong>{{ $empresa->nome }}</strong> ja esta ativa.
    </p>
    <p>
      Plano: <strong>{{ $plano->nome }}</strong><br />
      Limite de usuarios: <strong>{{ $plano->limite_usuarios }}</strong><br />
      Assinatura: <strong>{{ $assinatura->data_inicio }} ate {{ $assinatura->data_fim }}</strong>
    </p>
    <p>Bem-vindo ao ClickResto.</p>
  </body>
</html>
