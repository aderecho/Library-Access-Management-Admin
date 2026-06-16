@extends('layouts.admin', ['heading' => 'Add User Account'])
@section('content')
@include('admin.users.form', ['action' => route('admin.users.store'), 'method' => 'post', 'user' => null])
@endsection
