<!DOCTYPE html>
<html>
<head>
    <title>Redirection</title>
    <script>
        window.location.href = '{{ route(\
criteres-pointage.index\ }}';
    </script>
</head>
<body>
    <p>Redirection vers la page de configuration des critères de pointage...</p>
    <p>Si vous n'êtes pas redirigé automatiquement, <a href=\
{ route('criteres-pointage.index') }
\>cliquez ici</a>.</p>
</body>
</html>
