<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_article_with_sous_reserve_retour_false_as_string(): void
    {
        $response = $this->postJson('/api/articles', [
            'designation' => 'Article test Non',
            'actif' => '1',
            'sous_reserve_retour' => 'false',
        ]);

        $response->assertCreated()
            ->assertJsonPath('article.designation', 'Article test Non')
            ->assertJsonPath('article.sous_reserve_retour', false);

        $this->assertDatabaseHas('articles', [
            'designation' => 'Article test Non',
            'sous_reserve_retour' => 0,
        ]);
    }

    public function test_can_create_article_with_sous_reserve_retour_true_as_string(): void
    {
        $response = $this->postJson('/api/articles', [
            'designation' => 'Article test Oui',
            'actif' => 'true',
            'sous_reserve_retour' => 'true',
        ]);

        $response->assertCreated()
            ->assertJsonPath('article.sous_reserve_retour', true)
            ->assertJsonPath('article.actif', true);
    }

    public function test_can_create_article_with_sous_reserve_retour_as_zero_one(): void
    {
        $response = $this->postJson('/api/articles', [
            'designation' => 'Article test 0/1',
            'actif' => '0',
            'sous_reserve_retour' => '1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('article.actif', false)
            ->assertJsonPath('article.sous_reserve_retour', true);
    }

    public function test_can_update_article_sous_reserve_retour(): void
    {
        $article = Article::create([
            'code_article' => 'ART-TEST-001',
            'designation' => 'Article existant',
            'actif' => true,
            'sous_reserve_retour' => false,
        ]);

        $response = $this->putJson('/api/articles/'.$article->id, [
            'designation' => 'Article existant modifié',
            'actif' => '1',
            'sous_reserve_retour' => 'true',
        ]);

        $response->assertOk()
            ->assertJsonPath('article.sous_reserve_retour', true)
            ->assertJsonPath('article.designation', 'Article existant modifié');

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'sous_reserve_retour' => 1,
            'designation' => 'Article existant modifié',
        ]);
    }

    public function test_can_update_article_to_uncheck_sous_reserve_retour(): void
    {
        $article = Article::create([
            'code_article' => 'ART-TEST-002',
            'designation' => 'Article avec réserve',
            'actif' => true,
            'sous_reserve_retour' => true,
        ]);

        $response = $this->putJson('/api/articles/'.$article->id, [
            'designation' => 'Article avec réserve',
            'actif' => '1',
            'sous_reserve_retour' => '0',
        ]);

        $response->assertOk()
            ->assertJsonPath('article.sous_reserve_retour', false);
    }

    public function test_validation_errors_are_in_french(): void
    {
        $response = $this->postJson('/api/articles', [
            // designation missing → required
            'actif' => '1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['designation']);

        $message = $response->json('errors.designation.0');
        $this->assertNotNull($message);
        $this->assertStringContainsStringIgnoringCase('obligatoire', $message);
    }
}
