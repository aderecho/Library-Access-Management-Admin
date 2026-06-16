@extends('layouts.admin', ['heading' => 'Edit User Account'])
@section('content')
@include('admin.users.form', ['action' => route('admin.users.update', $user), 'method' => 'put'])
@endsection
