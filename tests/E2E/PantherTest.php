<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;

class PantherTest extends PantherTestCase
{
    public function testLoginAndNavigateToProfile(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/login');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'votre_nom_utilisateur';
        $form['_password'] = 'votre_mot_de_passe';

        $client->submit($form);

        $crawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $crawler = $client->click($crawler->selectLink('Mon profil')->link());

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $this->assertSelectorTextContains('h1', 'Profil');
    }
}
