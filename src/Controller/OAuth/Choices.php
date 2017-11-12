<?php
namespace App\Controller\OAuth;

use Psr\Log\LoggerInterface;
use Projek\Slim\Plates;
use App\Modules\OAuth\Handler;

class Choices
{
    /**
     * Use League\Container for auto-wiring dependencies into the controller
     * @param Handler         $handler [description]
     * @param Plates          $view    [description]
     * @param LoggerInterface $logger  [description]
     */
    public function __construct(
        Handler $handler,
        Plates $view,
        LoggerInterface $logger
    ) {
        $this->handler = $handler;
        $this->view = $view;
        $this->logger = $logger;
    }

    /**
     * Display authentication choices read from settings
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @param  [type] $args     [description]
     * @return [type]           [description]
     */
    public function display($request, $response, $args)
    {
        $available = $this->handler->getAvailable();

        return $this->view->render('oauth/choices.php', ['choices' => $available]);
    }
}