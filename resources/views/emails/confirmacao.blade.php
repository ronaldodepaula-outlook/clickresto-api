<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <title>Confirmacao de E-mail</title>
  </head>
  <body>
    <p>Ola {{ $usuario->nome }},</p>
    <p>
      Para ativar sua empresa <strong>{{ $empresa->nome }}</strong>,
      confirme seu e-mail clicando no link abaixo:
    </p>
    <p>
      <a href="{{ $confirmacaoUrl }}">{{ $confirmacaoUrl }}</a>
    </p>
    <p>Se voce nao solicitou, ignore esta mensagem.</p>
  </body>
</html>
