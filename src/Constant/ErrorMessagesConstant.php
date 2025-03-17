<?php

namespace App\Constant;

class ErrorMessagesConstant
{
    public const USER_NOT_FOUND = 'Utilisateur non trouvé';
    public const PROFILE_NOT_FOUND = 'Profil non trouvé';
    public const INTERNAL_SERVER_ERROR = 'Erreur interne du serveur';
    public const INVALID_DATA = 'Données invalides';
    public const INVALID_INPUT = 'Entrée invalide';
    public const TOKEN_NOT_FOUND = 'Jeton non trouvé';
    public const INVALID_TOKEN = 'Jeton invalide';
    public const TOKEN_EXPIRED = 'Jeton expiré';
    public const EMAIL_ALREADY_IN_USE = 'Email déjà utilisé';
    public const UNAUTHORIZED_ACCESS = 'Accès non autorisé';
    public const FORBIDDEN = 'Accès refusé';
    public const USER_ALREADY_ADMIN = 'Utilisateur déjà administrateur';
    public const ACCESS_DENIED = 'Accès refusé';
    public const USER_NOT_IN_GROUP = 'Utilisateur non dans le groupe';
    public const ONLY_ONE_PUBLIC_GROUP_ALLOWED = 'Un seul groupe public autorisé';
    public const GROUP_NOT_FOUND = 'Groupe non trouvé';
    public const USER_ALREADY_IN_GROUP = 'Utilisateur déjà dans le groupe';
    public const USER_ALREADY_HAS_ROLE = 'L\'utilisateur a déjà ce rôle';
    public const POST_NOT_FOUND = 'Publication non trouvée';
    public const CANNOT_POST_PUBLIC_IN_PRIVATE_GROUP = 'Impossible de publier publiquement dans un groupe privé';
}