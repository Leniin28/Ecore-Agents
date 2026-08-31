<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_visual_foundation_pages_are_available(): void
    {
        $routes = [
            route('home'),
            route('plans.index'),
            route('plans.custom'),
            route('login'),
            route('register'),
        ];

        foreach ($routes as $route) {
            $this->get($route)
                ->assertOk()
                ->assertSee('ECore Agents');
        }
    }

    public function test_each_existing_plan_uses_the_shared_detail_view(): void
    {
        foreach (['inicio', 'plus', 'avanzado'] as $plan) {
            $this->get(route('plans.show', $plan))
                ->assertOk()
                ->assertViewIs('plans.show');
        }
    }

    public function test_an_unknown_plan_returns_not_found(): void
    {
        $this->get(route('plans.show', 'inexistente'))->assertNotFound();
    }
}
