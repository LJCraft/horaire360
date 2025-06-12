@extends('layouts.app')

@section('title', 'Critères par département')

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Configuration des critères – Vue Département</h4>

    <!-- Filtre département -->
    <form method="GET" action="{{ route('criteres-pointage.departement') }}" class="row g-2 mb-4">
        <div class="col-auto">
            <select name="departement_id" class="form-select" required>
                <option value="">-- Choisir département --</option>
                @foreach($departements as $d)
                    <option value="{{ $d->id }}" {{ ($departementId==$d->id)?'selected':'' }}>{{ $d->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Filtrer</button>
        </div>
    </form>

    @if($departementId)
    <div class="row">
        <div class="col-md-4">
            <h6>Postes</h6>
            <ul class="list-group">
                @foreach($postes as $p)
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        {{ $p->nom }}
                        <span class="badge bg-primary rounded-pill">{{ $employesParPoste[$p->id]->count() ?? 0 }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-md-8">
            <table class="table table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Employé</th>
                        <th>Poste</th>
                        <th>Critère</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employesParPoste->flatten() as $emp)
                        @php $c=$critereParEmploye[$emp->id]??null; @endphp
                        <tr>
                            <td>{{ $emp->nom }} {{ $emp->prenom }}</td>
                            <td>{{ $emp->poste?->nom }}</td>
                            <td>
                                @if($c)
                                    <span class="badge bg-{{ $c->niveau=='departemental'?'danger':'success' }}">{{ ucfirst($c->niveau) }}</span>
                                @else
                                    <span class="text-muted">Aucun</span>
                                @endif
                            </td>
                            <td>
                                @if($c)
                                    <a href="{{ route('criteres-pointage.show-custom',$c->id) }}" class="btn btn-sm btn-outline-info">Voir</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
