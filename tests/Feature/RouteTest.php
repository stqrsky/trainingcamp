<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_route_points_to_an_existing_controller_method()
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (!str_contains($action, '@')) {
                continue; // closures
            }
            [$class, $method] = explode('@', $action);
            if (!method_exists($class, $method)) {
                $missing[] = implode('|', $route->methods()) . ' ' . $route->uri() . " → {$action}";
            }
        }

        $this->assertSame([], $missing, 'Routes without a controller method answer with a 500');
    }

    public function test_unused_resource_actions_are_not_a_server_error()
    {
        $user = User::factory()->create();
        Team::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        foreach (['/notification', '/notification/1', '/schedules/1', '/tasks/1'] as $uri) {
            $this->assertLessThan(500, $this->get($uri)->status(), $uri);
        }
    }
}
