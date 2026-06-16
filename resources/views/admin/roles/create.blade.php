@extends('layouts.admin', ['heading' => 'Add User Role'])
@section('content')
@include('admin.roles.form', ['action' => route('admin.roles.store'), 'method' => 'post', 'role' => null])
@endsection
