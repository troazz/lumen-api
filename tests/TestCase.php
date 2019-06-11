<?php

use App\Models\User;
use App\Repositories\Repository;
use Illuminate\Support\Arr;

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function _getAll($parameters)
    {
        $params = [];
        $total  = null;
        $count  = null;
        extract($parameters, EXTR_OVERWRITE);

        if ($total === null && $count === null) {
            $total = $model::count();
            $count = $total >= Repository::LIMIT ? Repository::LIMIT : $total;
        }
        $this->json('GET', $uri, $params)
            ->seeJsonStructure(['meta' => ['count', 'total'], 'data', 'links' => ['first', 'last', 'next', 'prev']])
            ->seeJsonContains(['meta' => ['count' => $count, 'total' => $total]])
            ->assertResponseOk();
        $content = $this->response->getOriginalContent();

        $this->assertTrue(count($content['data']) == $count);
        foreach ($content['links'] as $link) {
            if ($link) {
                $this->json('GET', $link)
                    ->seeJsonStructure(['meta' => ['count', 'total'], 'data', 'links' => ['first', 'last', 'next', 'prev']])
                    ->assertResponseOk();
            }
        }

        return $content;
    }

    protected function _getOne($parameters)
    {
        $params = [];
        extract($parameters, EXTR_OVERWRITE);

        $this->json('GET', $uri, $params)
            ->seeJsonStructure(['data' => ['type', 'id', 'attributes', 'links' => ['self']]])
            ->assertResponseOk();
        $content = $this->response->getOriginalContent();

        $this->assertTrue($content['data']['id'] == $id);
        $this->assertTrue($content['data']['type'] == $type);

        return $content;
    }

    protected function _insert($parameters)
    {
        extract($parameters, EXTR_OVERWRITE);

        $user = User::inRandomOrder()->first();

        $this->actingAs($user)
            ->json('POST', $uri, $body)
            ->seeJsonStructure(['data' => ['type', 'id', 'attributes', 'links' => ['self']]])
            ->assertResponseOk();
        $content = $this->response->getOriginalContent();

        if ($type != 'items') {
            $this->seeInDatabase($table, ['id' => $content['data']['id'], 'description' => $content['data']['attributes']['description']])
                ->assertTrue($content['data']['type'] == $type);
        }

        return $content;
    }

    protected function _update($parameters)
    {
        extract($parameters, EXTR_OVERWRITE);

        $user = User::inRandomOrder()->first();

        $this->actingAs($user)
            ->json('PATCH', $uri, $body)
            ->seeJsonStructure(['data' => ['type', 'id', 'attributes', 'links' => ['self']]])
            ->assertResponseOk();
        $content = $this->response->getOriginalContent();

        $this->assertTrue($content['data']['type'] == $type);
        $row = $model::find($id);
        foreach (Arr::only($body['data']['attributes'], ['object_domain', 'object_id', 'due', 'urgency', 'description', 'task_id', 'asignee_id']) as $attr => $value) {
            $this->assertTrue($row->$attr == $value);
        }

        return $content;
    }

    protected function _delete($parameters)
    {
        $params = [];
        extract($parameters, EXTR_OVERWRITE);

        $user = User::inRandomOrder()->first();
        $this->actingAs($user)
            ->json('DELETE', $uri, $params)
            ->assertResponseStatus(204);

        $row = $model::find($id);
        $this->assertTrue(! $row);
    }

    protected function _getFailed400($parameters)
    {
        $params = [];
        $method = 'GET';
        extract($parameters, EXTR_OVERWRITE);

        $user = User::inRandomOrder()->first();
        $this->actingAs($user)
            ->json($method, $uri, $params)
            ->seeJsonContains(['code' => '400'])
            ->assertResponseStatus(400);
    }

    protected function _getFailed404($parameters)
    {
        $params = [];
        $method = 'GET';
        extract($parameters, EXTR_OVERWRITE);

        $this->json($method, $uri, $params)
            ->seeJsonContains(['code' => '404'])
            ->assertResponseStatus(404);
    }
}
