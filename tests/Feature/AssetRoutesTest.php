<?php

describe('asset routes', function () {
    it('serves the CSS file with correct content type', function () {
        $response = $this->get('/dbv/assets/css/visualizer.css');

        $response->assertStatus(200);
        expect($response->headers->get('Content-Type'))->toContain('text/css');
    });

    it('serves the JS file with correct content type', function () {
        $this->get('/dbv/assets/js/visualizer.js')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/javascript');
    });

    it('returns 404 for unsupported asset types', function () {
        $this->get('/dbv/assets/img/logo.png')
            ->assertStatus(404);
    });

    it('returns 404 for non-existent files', function () {
        $this->get('/dbv/assets/css/nonexistent.css')
            ->assertStatus(404);
    });

    it('prevents path traversal attacks', function () {
        $this->get('/dbv/assets/css/../../../composer.json')
            ->assertStatus(404);
    });
});
