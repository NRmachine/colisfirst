@extends('layouts.market')
@section('title', 'Dossiers des utilisateurs')
@section('content')
<section class="wrap section"><a class="back" href="{{ route('dashboard') }}">Mon espace</a><h1 class="page-title">Dossiers des utilisateurs</h1>
<div class="table-wrap"><table><thead><tr><th>Utilisateur</th><th>Rôle</th><th>Coordonnées</th><th>Dossier</th></tr></thead><tbody>
@foreach($users as $user)
<tr><td>{{ $user->name }}</td><td>{{ $user->role }}</td><td>{{ $user->email }}<br>{{ $user->phone }}</td><td><a href="{{ route('account.admin.show',$user) }}">Consulter le dossier</a></td></tr>
@endforeach
</tbody></table></div>{{ $users->links() }}
</section>
@endsection
