@extends('layouts.admin', ['heading' => 'Branch Configuration'])

@section('content')
@if(auth()->user()->hasPermission('branches.create'))
<section class="panel form-panel">
    <div class="panel-heading"><div><span class="eyebrow">Facilities</span><h2>Add Branch</h2><p class="muted">Each branch gets an isolated scanner and entry-monitor stream.</p></div></div>
    <form method="post" action="{{ route('admin.branches.store') }}" class="form-grid">
        @csrf
        <label>Branch name<input name="name" value="{{ old('name') }}" maxlength="150" required></label>
        <label>Branch code<input name="code" value="{{ old('code') }}" maxlength="50" placeholder="e.g. SCIENCE" required></label>
        <div class="form-actions"><button class="primary" type="submit">Create branch</button></div>
    </form>
</section>
@endif

<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">Isolation</span><h2>Configured Branches</h2><p class="muted">Assign every physical scanner and branch monitor to the matching facility.</p></div></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Branch</th><th>Code</th><th>Scanners</th><th>Users</th><th>Status</th>@if(auth()->user()->hasPermission('branches.update'))<th>Actions</th>@endif</tr></thead>
        <tbody>@foreach($branches as $branch)<tr>
            <td><strong>{{ $branch->name }}</strong></td><td><code>{{ $branch->code }}</code></td><td>{{ $branch->scanner_tokens_count }}</td><td>{{ $branch->users_count }}</td><td><span class="badge {{ $branch->is_active ? 'valid' : 'invalid' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span></td>
            @if(auth()->user()->hasPermission('branches.update'))<td><details><summary>Edit</summary><form method="post" action="{{ route('admin.branches.update', $branch) }}" class="form-grid">@csrf @method('put')<label>Name<input name="name" value="{{ $branch->name }}" required></label><label>Code<input name="code" value="{{ $branch->code }}" required></label><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked($branch->is_active)> Active</label><button class="primary" type="submit">Save</button></form></details></td>@endif
        </tr>@endforeach</tbody>
    </table></div>
</section>
@endsection
