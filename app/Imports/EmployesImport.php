<?php

namespace App\Imports;

use App\Models\Employe;
use App\Models\Poste;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployesImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Trouver le poste par nom
            $poste = Poste::where('nom', $row['poste'])->first();
            
            if (!$poste) {
                // Créer le poste s'il n'existe pas
                $poste = Poste::create([
                    'nom' => $row['poste'],
                    'departement' => $row['departement'] ?? null,
                ]);
            }
            
            // Génération du matricule si non fourni
            $matricule = $row['matricule'] ?? 'EMP' . str_pad(Employe::max('id') + 1, 5, '0', STR_PAD_LEFT);
            
            // Création de l'employé
            $employe = Employe::create([
                'matricule' => $matricule,
                'nom' => $row['nom'],
                'prenom' => $row['prenom'],
                'email' => $row['email'],
                'telephone' => $row['telephone'] ?? null,
                'date_naissance' => $row['date_naissance'] ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_naissance']) : null,
                'date_embauche' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_embauche']),
                'poste_id' => $poste->id,
                'statut' => $row['statut'] ?? 'actif',
            ]);
            
            // Création d'un compte utilisateur si demandé
            if (isset($row['creer_compte']) && strtolower($row['creer_compte']) === 'oui') {
                $user = User::create([
                    'name' => $row['prenom'] . ' ' . $row['nom'],
                    'email' => $row['email'],
                    'password' => Hash::make('password'), // Mot de passe par défaut
                    'role_id' => 2, // Rôle Employé
                ]);
                
                $employe->update(['utilisateur_id' => $user->id]);
            }
        }
    }
    
    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'nom' => 'required',
            'prenom' => 'required',
            'email' => 'required|email|unique:employes,email',
            'date_embauche' => 'required',
            'poste' => 'required',
        ];
    }
}