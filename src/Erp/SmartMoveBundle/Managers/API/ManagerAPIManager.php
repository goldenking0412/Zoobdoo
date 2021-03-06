<?php

namespace Erp\SmartMoveBundle\Managers\API;

use Erp\SmartMoveBundle\Exceptions\SmartMoveManagerException;
use Erp\SmartMoveBundle\Managers\SmartMoveAbstractManager;
use Erp\SmartMoveBundle\Models\ManagerAPIModel;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Class RenterAPIManager
 *
 * @package Erp\SmartMoveBundle\SmartMove\Managers\SmartMoveManagers
 */
class ManagerAPIManager extends SmartMoveAbstractManager
{
    /**
     * Process API request
     *
     * @param string $method
     *
     * @return array
     * @throws \Erp\SmartMoveBundle\Exceptions\SmartMoveManagerException
     */
    public function proccess($method = '')
    {
        if (!$this->model instanceof ManagerAPIModel) {
            throw new InvalidArgumentException('Model must be instance of ManagerAPIModel class');
        }

        self::$apiEndpoint .= '/ManagerApi/v1';

        switch ($method) {
            case self::METHOD_PROPERTY_ADD:
                $response = $this->addProperty();
                break;
            case self::METHOD_APPLICATION_ADD:
                $response = $this->addApplication();
                break;
            case self::METHOD_APPLICANT_ADD:
                $response = $this->addApplicant();
                break;
            case self::METHOD_GET_REPORTS:
                $response = $this->getApplicationsReports();
                break;
            default:
                $available = [
                    self::METHOD_PROPERTY_ADD,
                    self::METHOD_APPLICATION_ADD,
                    self::METHOD_APPLICANT_ADD,
                    self::METHOD_GET_REPORTS,
                ];
                throw new SmartMoveManagerException(
                    sprintf(
                        'SmartMove method %s not found. Available methods are: %s',
                        $method,
                        implode(', ', $available)
                    )
                );
                break;
        }

        return $response;
    }

    /**
     * Add property
     *
     * @var model CustomerModel
     *
     * @return array
     */
    protected function addProperty()
    {
        $url = self::$apiEndpoint . '/Property';
        $params = json_encode($this->prepareNewPropertyParams());
        $header = [$this->getHeader(), 'Content-Type: application/json', 'Content-Length: ' . strlen($params)];

        $response = $this->curl->setHeaders($header)->setPostParams($params)->execute($url);
        $response = $this->proccessResponce($response);

        return $response;
    }

    /**
     * Add application
     *
     * @var model CustomerModel
     *
     * @return array
     */
    protected function addApplication()
    {
        $url = self::$apiEndpoint . '/Application';
        $params = json_encode($this->prepareNewApplicationParams());
        $header = [$this->getHeader(), 'Content-Type: application/json', 'Content-Length: ' . strlen($params)];

        $response = $this->curl->setHeaders($header)->setPostParams($params)->execute($url);
        $response = $this->proccessResponce($response);

        return $response;
    }

    /**
     * Add applicant
     *
     * @var model CustomerModel
     *
     * @return array
     */
    protected function addApplicant()
    {
        $smRenter = $this->model->getSMRenter();
        if (!$smRenter) {
            throw new InvalidArgumentException(
                'SmartMoveRenter entity not found. Use setSMRenter() in ManagerAPIModel class'
            );
        }
        $url = '/Applicant/Add?ApplicationId=' . $smRenter->getSmApplicationId() . '&Email=' . $smRenter->getEmail();
        $url = self::$apiEndpoint . $url;

        $response = $this->curl->setHeaders([$this->getHeader()])->setPostParams()->execute($url);
        $response = $this->proccessResponce($response);

        return $response;
    }

    protected function getApplicantStatus()
    {
        $smRenter = $this->model->getSMRenter();
        $url = '/Applicant/Add?ApplicationId=' . $smRenter->getSmApplicationId() . '&Email=' . $smRenter->getEmail();
        $url = self::$apiEndpoint . $url;

    }

    /**
     * Get applicant status
     *
     * @return array|mixed
     */
    protected function getApplicationsReports()
    {
        $smRenter = $this->model->getSMRenter();
        $url = self::$apiEndpoint . '/Application/' . $smRenter->getSmApplicationId();

        $header = [$this->getHeader()];

        $response = $this->curl->setHeaders($header)->execute($url);
        $response = $this->proccessResponce($response);

        return $response;
    }

    /**
     * Prepare params for new property request
     *
     * @return array
     */
    private function prepareNewPropertyParams()
    {
        /** @var $manager \Erp\UserBundle\Entity\User */
        $manager = $this->model->getSMRenter()->getManager();
        $property = $manager->getProperties()->first();
        $now = new \DateTime();
        $name = $property
            ? $property->getName()
            : $manager->getFirstName() . ' ' . $manager->getLastName() . ' Property('. $now->format('YmdHis') . ')';

        $name = substr($name, 0, 50); // limit to 50 symbols

        $city = $property ? $property->getCity()->getName() : $manager->getCity()->getName();
        $state = $property ? $property->getStateCode() : $manager->getState();
        $street = $property ? $property->getAddress() : $manager->getAddressOne();
        $zip = $property ? $property->getZip() : $manager->getPostalCode();

        for ($i = 1; $i < 6; $i++) {
            $questions[] = [
                'QuestionId'     => $i,
                'QuestionText'   => 'Text for Question ' . $i,
                'Options'        => [
                    ['AnswerText' => 'A', 'AnswerDescription' => 'Answer Description A'],
                    ['AnswerText' => 'B', 'AnswerDescription' => 'Answer Description B']
                ],
                'SelectedAnswer' => 'A'
            ];
        }

        return [
            'Active'                     => true,
            'City'                       => $city,
            'Classification'             => 'Conventional',
            'IR'                         => '0',
            'IsFcraAgreementAccepted'    => true,
            'Name'                       => $name,
            'FirstName'                  => $manager->getFirstName(),
            'LastName'                   => $manager->getLastName(),
            'Phone'                      => $manager->getPhoneDigitsOnly(),
            'State'                      => $state,
            'Street'                     => $street,
            'Zip'                        => $zip,
            'IncludeMedicalCollections'  => false,
            'IncludeForeclosures'        => false,
            'OpenBankruptcyWindow'       => '60',
            'DeclineForOpenBankruptcies' => false,
            'Questions'                  => $questions
        ];
    }

    protected function prepareNewApplicationParams()
    {
        return [
            'PropertyId'        => $this->model->getSMRenter()->getSmPropertyId(),
            'Deposit'           => '300',
            'Rent'              => '100',
            'LeaseTermInMonths' => '12',
            'ManagerPays'      => true,
            'ProductBundle'     => 'PackageCorePlusEviction',
            'Applicants'        => [$this->model->getSMRenter()->getEmail()]
        ];
    }
}
