@extends('layouts.admin', ['heading' => 'Edit User Role'])
@section('content')
@include('admin.roles.form', ['action' => route('admin.roles.update', $role), 'method' => 'put'])
@endsection
