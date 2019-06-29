<?php

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * @var string
     */
    private $document;

    /**
     * @var mixed
     */
    private $result;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given hay un documento :document
     */
    public function thereIsADocument($document)
    {
        $this->document = $document;
    }

    /**
     * @When ejecuto la consulta
     */
    public function executeConsult()
    {
        switch (strlen($this->document)) {
            case 8:
                $cs = new \Peru\Jne\Dni();
                $this->result = $cs->get($this->document);
                break;
            case 11:
                $cs = new \Peru\Sunat\Ruc();
                $this->result = $cs->get($this->document);
                break;
        }
    }

    /**
     * @Then La empresa deberia llamarse :name
     */
    public function theCompanyNameShouldBe($name)
    {
        if (empty($this->result)) {
            return;
        }

        /**@var $company \Peru\Sunat\Company */
        $company = $this->result;
        Assert::assertSame(
            $name,
            $company->razonSocial
        );
    }

    /**
     * @Then La persona deberia llamarse :name
     */
    public function thePersonNameShouldBe($name)
    {
        if (empty($this->result)) {
            return;
        }
        /**@var $person \Peru\Reniec\Person */
        $person = $this->result;
        Assert::assertSame(
            $name,
            $person->nombres
        );
    }
}
