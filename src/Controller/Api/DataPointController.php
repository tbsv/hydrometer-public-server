<?php

/*
 * This file is part of the hydrometer public server project.
 *
 * @author Clemens Krack <info@clemenskrack.com>
 */

namespace App\Controller\Api;

use App\Entity\DataPoint;
use App\Entity\Hydrometer;
use App\Modules\Auth\Token;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DataPointController extends AbstractController
{
    protected EntityManagerInterface$em;
    protected Token $tokenAuth;
    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        Token $tokenAuth,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->tokenAuth = $tokenAuth;
        $this->logger = $logger;
    }

    /**
     * Receive datapoint for hydrometer via HTTP POST.
     *
     * @Route("/api/ispindel/{token}", name="api-post-spindle")
     * @Route("/api/tilt/{token}", name="api-post-tilt")
     */
    public function __invoke($token, Request $request)
    {
        try {
            $data = $request->getContent();
            if ($data) {
                $data = json_decode($data, true);
            }
            $this->logger->debug('Spindle: Receive data', [$data, $token]);

            if (empty($data)) {
                $this->logger->debug('api::post: no data passed', [$token, $data]);
                throw new InvalidArgumentException('Api::post: No data passed');
            }

            if (!isset($token) && !(isset($data['ID'])) && isset($data['token'])) {
                $this->logger->debug('api::post: missing identifier', [$token, $data]);
                throw new InvalidArgumentException('Api::post: Data missing (ID or token)');
            }

            // confirm existance of the token @throws
            $authData = $this->tokenAuth->authenticate(empty($token) ? $data['token'] : $token);

            $hydrometer = $this->em->getRepository(Hydrometer::class)->find($authData['hydrometer_id']);

            $this->logger->debug('Spindle: Receive data for Hydrometer', [$hydrometer, $data]);

            // data needs to be changed possibly?
            $data = $this->prepareData($data);

            $dataPoint = new DataPoint();

            $dataPoint->import($data);
            $dataPoint->setHydrometer($hydrometer);

            // set interval on hydrometer if it's empty
            if (!$hydrometer->getInterval()) {
                $hydrometer->setInterval($dataPoint->getInterval());
            }

            $this->em->persist($hydrometer);
            $this->em->persist($dataPoint);

            $this->em->flush();

            return new JsonResponse((object) ['interval' => $authData['interval'] ?? $data['interval']], 200);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return new Response('', 500);
        }
    }

    /**
     * Prepare data for the import into Entity.
     *
     * @param array $data [description]
     *
     * @return array [description]
     */
    protected function prepareData(array $data): array
    {
        // prevent overwriting the ID by unsetting the espId
        if (isset($data['id'])) {
            unset($data['id']);
        }

        switch (true) {
            // TILT
            case isset($data['Timepoint']):
                $transformedData = [
                    'temperature' => $data['Temp'],
                    'gravity' => $data['SG'],
                ];

                return $transformedData;
            default:
                return $data;
        }
    }
}
