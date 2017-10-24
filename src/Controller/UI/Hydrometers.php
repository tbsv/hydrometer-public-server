<?php
namespace App\Controller\UI;

use Psr\Log\LoggerInterface;
use Projek\Slim\Plates;
use Doctrine\ORM\EntityManager;
use Jenssegers\Optimus\Optimus;
use AdamWathan\BootForms\BootForm;
use Valitron\Validator;
use App\Entity\User;
use App\Entity\Token;
use App\Entity\Hydrometer;

class Hydrometers
{
    /**
     * Use League\Container for auto-wiring dependencies into the controller
     * @param Plates          $view   [description]
     * @param LoggerInterface $logger [description]
     */
    public function __construct(
        EntityManager $em,
        Optimus $optimus,
        Plates $view,
        BootForm $form,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->optimus = $optimus;
        $this->view = $view;
        $this->logger = $logger;
        $this->form = $form;
    }

    /**
     * List of available hydrometers
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @param  [type] $args     [description]
     * @return [type]           [description]
     */
    public function display($request, $response, $args)
    {
        $user = $request->getAttribute('user');

        $hydrometers = $this->em->getRepository('App\Entity\Hydrometer')->findAllWithLastActivity($user);

        // render template
        return $this->view->render(
            '/ui/index.php',
            [
                'hydrometers' => $hydrometers,
                'optimus' => $this->optimus,
                'user' => $user
            ]
        );
    }

    /**
     * Add new Hydrometer
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @param  [type] $args     [description]
     * @return [type]           [description]
     */
    public function add($request, $response, $args)
    {
        try {
            $post = $request->getParsedBody();
            $user = $request->getAttribute('user');
            $user = $this->em->find(get_class($user), $user->getId());

            $validator = new Validator($post);
            $validator->rule('required', 'name');
            $validator->rule('required', 'metric_temp');
            $validator->rule('required', 'metric_gravity');

            if (! $request->isPost() || ! $validator->validate()) {
                $_SESSION['_old_input'] = $post;
                $this->setErrors($validator->errors());

                // render template
                return $this->view->render(
                    'ui/hydrometers/form.php',
                    [
                        'form' => $this->form,
                        'user' => $user
                    ]
                );
            }
            $_SESSION['_old_input'] = $post;

            $token = new Token;
            $token
                ->setType('device')
                ->setValue(bin2hex(random_bytes(10)))
                ->setUser($user);

            $this->em->persist($token);

            $hydrometer = new Hydrometer;
            $hydrometer
                ->setName($post['name'])
                ->setMetricTemperature($post['metric_temp'])
                ->setMetricGravity($post['metric_gravity'])
                ->setToken($token)
                ->setUser($user);

            $this->em->persist($hydrometer);
            $this->em->flush();

            return $response->withRedirect('/ui/hydrometers');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Edit Hydrometer
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @param  [type] $args     [description]
     * @return [type]           [description]
     */
    public function edit($request, $response, $args)
    {
        try {
            $post = $request->getParsedBody();
            $user = $request->getAttribute('user');
            $user = $this->em->find(get_class($user), $user->getId());
            $hydrometer = null;

            if (isset($args['hydrometer'])) {
                $args['hydrometer'] = $this->optimus->decode($args['hydrometer']);
                $hydrometer = $this->em->getRepository('App\Entity\Hydrometer')->findOneByUser($args['hydrometer'], $user);
            }

            $validator = new Validator($post);
            $validator->rule('required', 'name');
            $validator->rule('required', 'metric_temp');
            $validator->rule('required', 'metric_gravity');

            if (! $request->isPost() || ! $validator->validate()) {
                $_SESSION['_old_input'] = $post;
                $this->setErrors($validator->errors());

                // render template
                return $this->view->render(
                    'ui/hydrometers/editForm.php',
                    [
                        'form' => $this->form,
                        'hydrometer' => $hydrometer,
                        'user' => $user
                    ]
                );
            }
            $_SESSION['_old_input'] = $post;

            $hydrometer
                ->setName($post['name'])
                ->setMetricTemperature($post['metric_temp'])
                ->setMetricGravity($post['metric_gravity']);

            $this->em->persist($hydrometer);
            $this->em->flush();

            return $response->withRedirect('/ui/hydrometers');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    protected function setErrors($errors)
    {
        foreach ($errors as $key => $value) {
            $_SESSION['errors'][$key] = implode($value, '. ');
        }
    }

    /**
     * issue a new device token and display it, pinging for a new hydrometer
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @param  [type] $args     [description]
     * @return [type]           [description]
     */
    public function help($request, $response, $args)
    {
        $user = $request->getAttribute('user');
        $user = $this->em->find(get_class($user), $user->getId());
        $hydrometer = null;

        if (isset($args['hydrometer'])) {
            $args['hydrometer'] = $this->optimus->decode($args['hydrometer']);
            $hydrometer = $this->em->getRepository('App\Entity\Hydrometer')->findOneByUser($args['hydrometer'], $user);
        }

        $token = $hydrometer->getToken();

        // render template
        return $this->view->render(
            '/ui/hydrometers/help.php',
            [
                'token' => $token,
                'hydrometer' => $hydrometer,
                'optimus' => $this->optimus,
                'user' => $user
            ]
        );
    }
}