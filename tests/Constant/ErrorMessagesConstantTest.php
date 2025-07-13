<?php

namespace App\Tests\Constant;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use PHPUnit\Framework\TestCase;

class ErrorMessagesConstantTest extends TestCase
{
    public function testErrorMessagesConstants(): void
    {
        $this->assertEquals('Utilisateur non trouvé', UserErrorMessagesConstant::USER_NOT_FOUND);
        $this->assertEquals('Profil non trouvé', ProfileErrorMessagesConstant::PROFILE_NOT_FOUND);
        $this->assertEquals('Erreur interne du serveur', GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        $this->assertEquals('Données invalides', GenericErrorMessagesConstant::INVALID_DATA);
        $this->assertEquals('Entrée invalide', GenericErrorMessagesConstant::INVALID_INPUT);
        $this->assertEquals('Jeton non trouvé', SecurityErrorMessagesConstant::TOKEN_NOT_FOUND);
        $this->assertEquals('Jeton invalide', SecurityErrorMessagesConstant::INVALID_TOKEN);
        $this->assertEquals('Jeton expiré', SecurityErrorMessagesConstant::TOKEN_EXPIRED);
        $this->assertEquals('Email déjà utilisé', UserErrorMessagesConstant::EMAIL_ALREADY_IN_USE);
        $this->assertEquals('Accès non autorisé', SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS);
        $this->assertEquals('Accès refusé', SecurityErrorMessagesConstant::FORBIDDEN);
    }
}