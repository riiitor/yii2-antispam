<?php
namespace cleantalk\antispam\tests;

use cleantalk\antispam\Component as CleantalkComponent;
use cleantalk\antispam\validators\MessageValidator;
use lib\CleantalkResponse;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Model;

/**
 * @coversDefaultClass \cleantalk\antispam\Api\validators\MessageValidatorTest
 */
class MessageValidatorTest extends TestCase
{
    protected function setUp()
    {
        @session_start();
    }

    protected function setResponse($allow, $message)
    {
        $mock = $this->getMockBuilder(CleantalkComponent::className())
            ->setMethods(['sendRequest'])
            ->setConstructorArgs([['apiKey' => CLEANTALK_TEST_API_KEY]])
            ->getMock();

        $mock->expects($this->once())
            ->method('sendRequest')
            ->will(
                $this->returnValue(
                    new CleantalkResponse(
                        [
                            'allow' => $allow,
                            'comment' => $message,
                        ]
                    )
                )
            );

        Yii::$app->set('antispam', $mock);
    }

    public function testValidator()
    {
        $this->setResponse(0, 'Forbidden');

        $model = new FakeModel();
        $model->message = 'example1';
        $model->validate();
        $this->assertTrue($model->hasErrors('message'));
    }

    public function testValidatorAllow()
    {
        $this->setResponse(1, '');

        $model = new FakeModel();
        $model->message = 'example1';
        $model->validate();
        $this->assertFalse($model->hasErrors('message'));
    }
}

class FakeModel extends Model
{
    public $message;

    public function rules()
    {
        return [
            ['message', MessageValidator::className()]
        ];
    }
}