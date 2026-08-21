<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

</head>
<body>
    <main>
        <section>
            <div class='container p-5'>
                <h1 class='pb-3'>Consulta</h1>
                <p><strong>Nombre: </strong><br>{{$data->nombre}}</p>
                <p><strong>Teléfono: </strong><br>{{$data->telefono}}</p>
                <p><strong>Email: </strong><br>{{$data->mail}}</p>
                @if(isset($data->empresa))
                <p><strong>Empresa: </strong><br>{{$data->empresa}}</p>
                @endif
                @if(isset($data->mensaje))
                <p><strong>Mensaje</strong><br>{{$data->mensaje}}</p>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
