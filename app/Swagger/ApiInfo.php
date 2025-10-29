<?php

namespace App\Swagger;

/**
 * @OA\OpenApi(
 *     openapi="3.0.0",
 *     @OA\Info(
 *         title="API de Gestion Bancaire",
 *         version="1.0.0",
 *         description="API REST pour la gestion des comptes bancaires, clients et transactions",
 *         @OA\Contact(
 *             email="contact@banque.com"
 *         ),
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000/api/v1",
 *         description="Serveur de développement"
 *     ),
 *     @OA\Server(
 *         url="https://gestion-bancairee-5.onrender.com/api/v1",
 *         description="Serveur de production"
 *     ),
 *     @OA\Tag(
 *         name="Clients",
 *         description="Gestion des clients"
 *     ),
 *     @OA\Tag(
 *         name="Comptes Bancaires",
 *         description="Gestion des comptes bancaires"
 *     ),
 *     @OA\Tag(
 *         name="Transactions",
 *         description="Gestion des transactions"
 *     ),
 *     @OA\Tag(
 *         name="Utilisateurs",
 *         description="Gestion des utilisateurs (Admin/Client)"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Dupont"),
 *     @OA\Property(property="prenom", type="string", example="Jean"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="adresse", type="string", example="123 Rue de la Paix"),
 *     @OA\Property(property="ville", type="string", example="Paris"),
 *     @OA\Property(property="code_postal", type="string", example="75001"),
 *     @OA\Property(property="pays", type="string", example="France"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "suspendu"}, example="actif"),
 *     @OA\Property(property="numero_client", type="string", example="CLI001"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CompteBancaire",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
 *     @OA\Property(property="numero_compte", type="string", example="FR7630001007941234567890185"),
 *     @OA\Property(property="type_compte", type="string", enum={"cheque", "epargne"}, example="cheque"),
 *     @OA\Property(property="devise", type="string", example="EUR"),
 *     @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *     @OA\Property(property="solde_formate", type="string", example="1 500,50 EUR"),
 *     @OA\Property(property="decouvert_autorise", type="number", format="float", example=500.00),
 *     @OA\Property(property="date_ouverture", type="string", format="date", example="2023-01-15"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloque"}, example="actif"),
 *     @OA\Property(property="peut_debiter", type="boolean", example=true),
 *     @OA\Property(property="est_bloque", type="boolean", example=false),
 *     @OA\Property(property="date_fin_blocage", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="motif_blocage", type="string", nullable=true),
 *     @OA\Property(property="est_archive", type="boolean", example=false),
 *     @OA\Property(property="client", ref="#/components/schemas/Client"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002"),
 *     @OA\Property(property="type_transaction", type="string", enum={"debit", "credit", "virement_emis", "virement_recu"}, example="debit"),
 *     @OA\Property(property="montant", type="number", format="float", example=100.00),
 *     @OA\Property(property="libelle", type="string", example="Paiement facture EDF"),
 *     @OA\Property(property="description", type="string", example="Paiement de la facture d'électricité"),
 *     @OA\Property(property="date_transaction", type="string", format="date", example="2023-10-23"),
 *     @OA\Property(property="statut", type="string", enum={"validee", "annulee", "en_attente"}, example="validee"),
 *     @OA\Property(property="est_archive", type="boolean", example=false),
 *     @OA\Property(property="compte_bancaire_id", type="string", format="uuid"),
 *     @OA\Property(property="compte_bancaire_destinataire_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="devise", type="string", example="EUR"),
 *     @OA\Property(property="compteBancaire", ref="#/components/schemas/CompteBancaire"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440003"),
 *     @OA\Property(property="name", type="string", example="Jean Dupont"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="role", type="string", enum={"admin", "client"}, example="client"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif"}, example="actif"),
 *     @OA\Property(property="client_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="last_page", type="integer", example=7),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=15),
 *     @OA\Property(property="next_page_url", type="string", nullable=true),
 *     @OA\Property(property="prev_page_url", type="string", nullable=true)
 * )
 */
class ApiInfo
{
    // Cette classe sert uniquement à contenir les annotations Swagger
}