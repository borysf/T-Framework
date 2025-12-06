<?php

namespace System\Web\Service\System;

use System\Http\Error\THttpError;
use System\Http\THttpCode;
use System\TApplication;
use System\Web\Action\Action;
use System\Web\Service\TService;
use System\Web\Action\TActionArgs;
use System\Web\Page\AssetBundler\TAssetBundler;

/**
 * Internal framework service for sending assets and static files.
 */
class TMediaService extends TService
{
    /** 
     * Action called by `/images/*` route. Sends given file from `<Project>/Assets/images` directory.
     */
    #[Action(name: 'sendImage', cacheControl: 'max-age=8640, must-revalidate')]
    public static function sendImage(TActionArgs $args, TApplication $application): void
    {
        self::__serve('images', $args->file, $application);
    }

    /** 
     * Action called by `/styles/*` route. Sends given file from `<Project>/Assets/styles` directory.
     */
    #[Action(name: 'sendStyle', cacheControl: 'max-age=8640, must-revalidate')]
    public static function sendStyle(TActionArgs $args, TApplication $application): void
    {
        self::__serve('styles', $args->file, $application);
    }

    /** 
     * Action called by `/scripts/*` route. Sends given file from `<Project>/Assets/scripts` directory.
     */
    #[Action(name: 'sendScript', cacheControl: 'max-age=8640, must-revalidate')]
    public static function sendScript(TActionArgs $args, TApplication $application): void
    {
        self::__serve('scripts', $args->file, $application);
    }

    /** 
     * Action called by `/static/*` route. Sends given file from `<Project>/Static` directory.
     */
    #[Action(name: 'sendStatic', cacheControl: 'max-age=8640, must-revalidate')]
    public static function sendStatic(TActionArgs $args, TApplication $application): void
    {
        $root = TApplication::getRootDir() . 'Static' . DIRECTORY_SEPARATOR;

        $file = realpath($root . $args->file);

        if (!$file || ($file && strpos($file, $root) !== 0)) {
            throw new THttpError(THttpCode::NOT_FOUND);
        }

        $application->response->sendFile($file);
    }

    /**
     * 
     */
    #[Action(name: 'sendAssetFromBundle', cacheControl: 'max-age=8640, must-revalidate')]
    public static function sendAssetFromBundle(TActionArgs $args, TApplication $application): void
    {
        $path = TAssetBundler::getAssetPath($args->bundleId, $args->file);

        if (!$path) {
            throw new THttpError(THttpCode::NOT_FOUND);
        }

        $application->response->sendFile($path);
    }

    private static function __serve(string $dirname, string $file, TApplication $application)
    {
        $root = TApplication::getRootDir() . 'Assets' . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR;

        $file = realpath($root . $file);

        if (!$file || ($file && strpos($file, $root) !== 0)) {
            throw new THttpError(THttpCode::NOT_FOUND);
        }

        $application->response->sendFile($file);
    }
}
