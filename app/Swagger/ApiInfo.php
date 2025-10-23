<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     title="API de Gestion Bancaire",
 *     version="1.0.0",
 *     description="API REST pour la gestion des comptes bancaires, clients et transactions",
 *     @OA\Contact(
 *         email="contact@banque.com"
 *     ),
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients"
 * )
 *
 * @OA\Tag(
 *     name="Comptes Bancaires",
 *     description="Gestion des comptes bancaires"
 * )
 *
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions"
 * )
 *
 * @OA\Tag(
 *     name="Utilisateurs",
 *     description="Gestion des utilisateurs (Admin/Client)"
 * )
 */