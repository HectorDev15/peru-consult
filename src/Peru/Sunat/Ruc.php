<?php

/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 15/11/2017
 * Time: 04:15 PM.
 */

namespace Peru\Sunat;

use Peru\Http\ClientInterface;
use Peru\Services\RucInterface;

/**
 * Class Ruc.
 */
class Ruc implements RucInterface
{
    use RandomTrait;

    /**
     * @var ClientInterface
     */
    public $client;
    /**
     * @var RucParser
     */
    private $parser;

    /**
     * Ruc constructor.
     *
     * @param ClientInterface $client
     * @param RucParser       $parser
     */
    public function __construct(ClientInterface $client, RucParser $parser)
    {
        $this->client = $client;
        $this->parser = $parser;
    }

    /**
     * Get Company Information by RUC.
     *
     * @param string $ruc
     *
     * @return null|Company
     */
    public function get(string $ruc): ?Company
    {
        $this->client->get(Endpoints::CONSULT);
        $htmlRandom = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorRazonSoc',
            'razSoc' => 'BVA FOODS',
        ]);

        $random = $this->getRandom($htmlRandom);

        $html = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorRuc',
            'nroRuc' => $ruc,
            'numRnd' => $random,
            'actReturn' => '1',
            'modo' => '1',
        ]);

        return $html === false ? null : $this->parser->parse($html);
    }

    /**
     * Get RUC by CE.
     *
     * @param string $ce
     * @param string $type
     *
     * @return null|array
     */
    public function getRucsByDoc(string $ce, string $type): ?array
    {
        $this->client->get(Endpoints::CONSULT);
        $htmlRandom = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorRazonSoc',
            'razSoc' => 'BVA FOODS',
        ]);

        $random = $this->getRandom($htmlRandom);

        $html = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorTipdoc',
            'rbtnTipo' => '2',
            'tipdoc' => $type,
            'nrodoc' => $ce,
            'search2' => $ce,
            'numRnd' => $random,
            'actReturn' => '1',
            'modo' => '1',
        ]);

        return $this->parser->parseRuc($html);
    }

    /**
     * Get Deuda Coactiva.
     *
     * @param string $ruc
     *
     * @return null|array
     */
    public function getRucDeudaCoactiva(string $ruc): ?array
    {
        $this->client->get(Endpoints::CONSULT);
        $htmlRandom = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'consPorRazonSoc',
            'razSoc' => 'BVA FOODS',
        ]);

        $random = $this->getRandom($htmlRandom);

        $html = $this->client->post(Endpoints::CONSULT, [
            'accion' => 'getInfoDC',
            'nroRuc' => $ruc,
            'numRnd' => $random,
            'actReturn' => '1',
            'modo' => '1',
        ]);

        $parse = $this->parser->parseDeuda($html);

        return $parse;
    }

    /**
     * Get Representantes Legales.
     *
     * @param string $ruc
     *
     * @return null|array
     */
    public function getRucRepresentantes(string $ruc): ?array
    {
        $company = $this->get($ruc);
        $parse = [];

        if ($company) {
            $razon = $company->razonSocial;

            $html = $this->client->post(Endpoints::CONSULT, [
                'accion' => 'getRepLeg',
                'nroRuc' => $ruc,
                'contexto' => 'ti-it',
                'desRuc' => $razon,
                'modo' => '1',
            ]);

            $parse = $this->parser->parseRepresentante($html);
        }

        return $parse;
    }
}
