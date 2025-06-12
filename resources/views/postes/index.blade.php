@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6">
            <h1>Gestion des postes</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('postes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouveau poste
            </a>
        </div>
    </div>

    <!-- Messages de notification -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Département</th>
                            <th>Description</th>
                            <th>Nb. Employés</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($postes as $poste)
                            <tr>
                                <td class="fw-bold">{{ $poste->nom }}</td>
                                <td>{{ $poste->departement }}</td>
                                <td>{{ Str::limit($poste->description, 50) }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $poste->employes_count }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('postes.edit', $poste) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="event.preventDefault(); 
                                                 if(confirm('Êtes-vous sûr de vouloir supprimer ce poste?')) {
                                                     document.getElementById('delete-form-{{ $poste->id }}').submit();
                                                 }">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $poste->id }}" action="{{ route('postes.destroy', $poste) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Aucun poste trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection