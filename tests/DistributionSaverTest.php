<?php
// tests/DistributionSaverTest.php

require_once __DIR__ . '/../classes/ApiClient.php';
require_once __DIR__ . '/../classes/DistributionSaver.php';

class DistributionSaverTest extends PHPUnit\Framework\TestCase
{
    private function createMockApiClient(): ApiClient
    {
        return $this->getMockBuilder(ApiClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWorkloadByNumber', 'getTeachers', 'postAddEmployeeToRaspredPoruch'])
            ->getMock();
    }

    public function testSaveWithNewData(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '5'],
                ]
            ]
        ];

        $teachers = [
            ['Сотрудник' => 'Иванов И.И.', 'Должность' => 'Профессор', 'Ставка' => '1.0'],
            ['Сотрудник' => 'Петров П.П.', 'Должность' => 'Доцент', 'Ставка' => '0.5'],
        ];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);
        $api->method('postAddEmployeeToRaspredPoruch')->willReturn(['status' => 'ok']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', [
            'row1' => [
                'Иванов И.И.' => 10,
                'Петров П.П.' => 5,
                '_base' => 0
            ]
        ]);

        $result = $saver->save();
        $this->assertEquals(['status' => 'ok'], $result);
    }

    public function testSaveWithNoChanges(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => [
                    ['Сотрудник' => 'Иванов И.И.', 'Количество' => '10'],
                ]
            ]
        ];

        $teachers = [
            ['Сотрудник' => 'Иванов И.И.', 'Должность' => 'Профессор', 'Ставка' => '1.0'],
        ];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', [
            'row1' => [
                'Иванов И.И.' => 10,
                '_base' => 0
            ]
        ]);

        $result = $saver->save();
        $this->assertEquals(['status' => 'ok', 'message' => 'no changes'], $result);
    }

    public function testSaveWithConnectionErrorOnOldData(): void
    {
        $api = $this->createMockApiClient();
        $api->method('getWorkloadByNumber')->willReturn(['error_type' => 'connection']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', ['row1' => ['Иванов И.И.' => 10]]);

        $result = $saver->save();
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ошибка получения данных из 1С', $result['message']);
    }

    public function testSaveWithConnectionErrorOnTeachers(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => [['Сотрудник' => 'Иванов И.И.', 'Количество' => '5']]
            ]
        ];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn(['error_type' => 'connection']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', ['row1' => ['Иванов И.И.' => 10]]);

        $result = $saver->save();
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ошибка получения списка преподавателей', $result['message']);
    }

    public function testSaveWithConnectionErrorOnPost(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => [['Сотрудник' => 'Иванов И.И.', 'Количество' => '5']]
            ]
        ];

        $teachers = [
            ['Сотрудник' => 'Иванов И.И.', 'Должность' => 'Профессор', 'Ставка' => '1.0'],
        ];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);
        $api->method('postAddEmployeeToRaspredPoruch')->willReturn(['error_type' => 'connection']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', ['row1' => ['Иванов И.И.' => 10]]);

        $result = $saver->save();
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ошибка сохранения в 1С', $result['message']);
    }

    public function testSaveSkipsBaseField(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => []
            ]
        ];

        $teachers = [];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', [
            'row1' => [
                '_base' => 10,
                'Иванов И.И.' => 0
            ]
        ]);

        $result = $saver->save();
        $this->assertEquals(['status' => 'ok', 'message' => 'no changes'], $result);
    }

    public function testSaveWithEmptyOldEmployees(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => []
            ]
        ];

        $teachers = [
            ['Сотрудник' => 'Иванов И.И.', 'Должность' => 'Профессор', 'Ставка' => '1.0'],
        ];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);
        $api->method('postAddEmployeeToRaspredPoruch')->willReturn(['status' => 'ok']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', [
            'row1' => ['Иванов И.И.' => 10]
        ]);

        $result = $saver->save();
        $this->assertEquals(['status' => 'ok'], $result);
    }

    public function testSaveWithMissingTeacherMeta(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => []
            ]
        ];

        $teachers = [];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);
        $api->method('postAddEmployeeToRaspredPoruch')->willReturn(['status' => 'ok']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', [
            'row1' => ['Новый Преподаватель' => 10]
        ]);

        $result = $saver->save();
        $this->assertEquals(['status' => 'ok'], $result);
    }

    public function testSaveWithZeroValues(): void
    {
        $api = $this->createMockApiClient();

        $oldData = [
            [
                'УникальныйИдентификатор' => 'row1',
                'Сотрудники' => [['Сотрудник' => 'Иванов И.И.', 'Количество' => '10']]
            ]
        ];

        $teachers = [
            ['Сотрудник' => 'Иванов И.И.', 'Должность' => 'Профессор', 'Ставка' => '1.0'],
        ];

        $api->method('getWorkloadByNumber')->willReturn($oldData);
        $api->method('getTeachers')->willReturn($teachers);
        $api->method('postAddEmployeeToRaspredPoruch')->willReturn(['status' => 'ok']);

        $saver = $this->createSaver($api, '000000033', 'Кафедра');
        $this->setPrivateProperty($saver, 'newData', ['row1' => ['Иванов И.И.' => 0]]);

        $result = $saver->save();
        $this->assertEquals(['status' => 'ok'], $result);
    }

    private function createSaver(ApiClient $api, string $number, string $name): DistributionSaver
    {
        $saver = new class extends DistributionSaver {
            public function __construct() {}
        };

        $this->setPrivateProperty($saver, 'api', $api);
        $this->setPrivateProperty($saver, 'documentNumber', $number);
        $this->setPrivateProperty($saver, 'documentName', $name);

        return $saver;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);

        while ($reflection && !$reflection->hasProperty($property)) {
            $reflection = $reflection->getParentClass();
        }

        if ($reflection) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($object, $value);
        }
    }
}