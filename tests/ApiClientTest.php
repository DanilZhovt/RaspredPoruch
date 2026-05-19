<?php
// tests/ApiClientTest.php

require_once __DIR__ . '/../classes/ApiClient.php';

class ApiClientTest extends PHPUnit\Framework\TestCase
{
    private string $testUrl = 'http://test-server.com/api';

    private function createMockClient(): ApiClient
    {
        return $this->getMockBuilder(ApiClient::class)
            ->setConstructorArgs([$this->testUrl, 'test_user', 'test_pass'])
            ->onlyMethods(['request'])
            ->getMock();
    }

    public function testIsConnectionErrorWithTimeout(): void
    {
        $response = ['error_type' => 'timeout', 'error' => 'Request timed out'];
        $this->assertTrue(ApiClient::isConnectionError($response));
    }

    public function testIsConnectionErrorWithConnection(): void
    {
        $response = ['error_type' => 'connection', 'error' => 'Could not connect'];
        $this->assertTrue(ApiClient::isConnectionError($response));
    }

    public function testIsConnectionErrorWithOtherError(): void
    {
        $response = ['error' => 'Some error'];
        $this->assertFalse(ApiClient::isConnectionError($response));
    }

    public function testIsConnectionErrorWithEmptyArray(): void
    {
        $this->assertFalse(ApiClient::isConnectionError([]));
    }

    public function testGetWorkloadByNumberValidData(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'РасчетЧасов' => [
                [
                    'Дисциплина' => 'Математика',
                    'Нагрузка' => 'Лекция',
                    'Количество' => '36',
                    'Распределено' => '18',
                    'Сотрудники' => [['Сотрудник' => 'Иванов', 'Количество' => '18']]
                ]
            ]
        ]);

        $result = $client->getWorkloadByNumber('000000033');
        $this->assertIsArray($result);
        $this->assertEquals('Математика', $result[0]['Дисциплина']);
    }

    public function testGetWorkloadByNumberNotFound(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'error' => 'HTTP Error: 500',
            'http_code' => 500,
            'response' => 'Элемент не выбран'
        ]);

        $result = $client->getWorkloadByNumber('000000035');
        $this->assertTrue($result['error']);
        $this->assertEquals('Документ с номером 000000035 не найден', $result['message']);
    }

    public function testGetWorkloadByNumber1CError(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'error' => 'HTTP Error: 400',
            'http_code' => 400,
            'response' => 'Ошибка: Некорректные параметры'
        ]);

        $result = $client->getWorkloadByNumber('invalid');
        $this->assertTrue($result['error']);
        $this->assertStringContainsString('Некорректные параметры', $result['message']);
    }

    public function testGetWorkloadByNumberInvalidJson(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'error' => 'Invalid JSON response',
            'raw_response' => 'Not a JSON'
        ]);

        $result = $client->getWorkloadByNumber('123');
        $this->assertTrue($result['error']);
        $this->assertStringContainsString('Invalid JSON', $result['message']);
    }

    public function testGetWorkloadByNumberMissingField(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn(['SomeOtherField' => []]);

        $result = $client->getWorkloadByNumber('123');
        $this->assertTrue($result['error']);
        $this->assertEquals('Некорректный формат ответа от сервера', $result['message']);
        $this->assertTrue($result['not_found']);
    }

    public function testGetAllWorkloads(): void
    {
        $client = $this->getMockBuilder(ApiClient::class)
            ->setConstructorArgs([$this->testUrl, 'user', 'pass'])
            ->onlyMethods(['getAllWorkloads'])
            ->getMock();

        $expected = ['РасчетЧасов' => []];
        $client->method('getAllWorkloads')->willReturn($expected);

        $result = $client->getAllWorkloads();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('РасчетЧасов', $result);
    }

    public function testGetTeachersFiltersDismissed(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'data' => [
                [
                    'Сотрудник' => 'Иванов', 'Должность' => 'Доцент',
                    'ВидСобытия' => 'Прием', 'ДатаНачала' => '2020-01-01'
                ],
                [
                    'Сотрудник' => 'Иванов', 'Должность' => 'Доцент',
                    'ВидСобытия' => 'Увольнение', 'ДатаНачала' => '2023-01-01'
                ]
            ]
        ]);

        $result = $client->getTeachers('Кафедра');
        $this->assertEmpty($result);
    }

    public function testGetTeachersFiltersByPosition(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'data' => [
                [
                    'Сотрудник' => 'Иванов', 'Должность' => 'Профессор',
                    'ВидСобытия' => 'Прием', 'ДатаНачала' => '2020-01-01'
                ],
                [
                    'Сотрудник' => 'Петров', 'Должность' => 'Доцент',
                    'ВидСобытия' => 'Прием', 'ДатаНачала' => '2020-01-01'
                ]
            ]
        ]);

        $result = $client->getTeachers('Кафедра');
        $this->assertCount(1, $result);
        $this->assertEquals('Петров', $result[0]['Сотрудник']);
    }

    public function testGetTeachersFiltersFutureDates(): void
    {
        $client = $this->createMockClient();
        $futureDate = (new DateTimeImmutable('+1 year'))->format('Y-m-d');

        $client->method('request')->willReturn([
            'data' => [
                [
                    'Сотрудник' => 'Иванов', 'Должность' => 'Доцент',
                    'ВидСобытия' => 'Прием', 'ДатаНачала' => $futureDate
                ]
            ]
        ]);

        $result = $client->getTeachers('Кафедра');
        $this->assertEmpty($result);
    }

    public function testGetTeachersHandlesTransfer(): void
    {
        $client = $this->createMockClient();
        $client->method('request')->willReturn([
            'data' => [
                [
                    'Сотрудник' => 'Иванов', 'Должность' => 'Доцент',
                    'ВидСобытия' => 'Прием', 'ДатаНачала' => '2020-01-01'
                ],
                [
                    'Сотрудник' => 'Иванов', 'Должность' => 'Старший преподаватель',
                    'ВидСобытия' => 'Перемещение', 'ДатаНачала' => '2022-01-01'
                ]
            ]
        ]);

        $result = $client->getTeachers('Кафедра');
        $this->assertCount(1, $result);
        $this->assertEquals('Старший преподаватель', $result[0]['Должность']);
    }
}