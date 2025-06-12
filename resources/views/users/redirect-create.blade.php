@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Création d'un compte utilisateur</div>
                <div class="card-body text-center">
                    <p>Création du compte utilisateur en cours...</p>
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    
                    <form id="autoSubmitForm" action="{{ route('users.create-from-employee', $employe) }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Soumettre automatiquement le formulaire dès que la page est chargée
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('autoSubmitForm').submit();
    });
</script>
@endpush 