<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <form action="{{ route('store') }}" method="POST">
        @csrf
        <input type="text" name="title" placeholder="title">
        {{$errors->first('title')}}
        <input type="text" name="description" placeholder="description">
        {{$errors->first('description')}}
        <input type="text" name="time" placeholder="time">
        {{$errors->first('time')}}
        <input type="text" name="user_id" placeholder="user_id">
        {{$errors->first('user_id')}}
        <input type="submit" value="save">
    </form>
</body>
</html>