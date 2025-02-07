<?php
namespace cleantalk\antispam\tests;

use cleantalk\antispam\Component as CleantalkComponent;
use InvalidArgumentException;
use lib\CleantalkRequest;
use lib\CleantalkResponse;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Yii;

/**
 * @coversDefaultClass \cleantalk\antispam\Component
 */
class ComponentTest extends TestCase
{
    /**
     * @var \cleantalk\antispam\Component
     */
    protected $component;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        @session_start();
        $this->component = Yii::createObject(['class' => CleantalkComponent::className(), 'apiKey' => CLEANTALK_TEST_API_KEY]);
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testInit()
    {
        Yii::createObject(['class' => CleantalkComponent::className(), 'apiKey' => null]);
    }

    public function testIsAllowUser()
    {
        $mock = $this->getSendRequestMock(
            array(
                'allow' => 1,
                'comment' => ''
            )
        );
        list($result, $comment) = $mock->isAllowUser('allow@email.ru', 'Ivan Petrov');
        $this->assertTrue($result);

        $mock = $this->getSendRequestMock(
            array(
                'allow' => 0,
                'comment' => 'Mock deny'
            )
        );
        list($result, $comment) = $mock->isAllowUser('deny@email.ru', 'Ivan Petrov');
        $this->assertFalse($result);
        $this->assertEquals('Mock deny', $comment);

        $mock = $this->getSendRequestMock(
            array(
                'inactive' => 1,
                'comment' => 'Mock deny'
            )
        );
        list($result, $comment) = $mock->isAllowUser('deny@email.ru', 'Ivan Petrov');
        $this->assertFalse($result);
    }

    public function testIsAllowMessage()
    {
        $mock = $this->getSendRequestMock(
            array(
                'allow' => 1,
                'comment' => ''
            )
        );

        list($result, $comment) = $mock->isAllowMessage('message1');
        $this->assertTrue($result);

        $mock = $this->getSendRequestMock(
            array(
                'allow' => 0,
                'comment' => 'Mock deny'
            )
        );
        list($result, $comment) = $mock->isAllowMessage('bad message');
        $this->assertFalse($result);
        $this->assertEquals('Mock deny', $comment);
    }

    public function testGetCheckJsCode()
    {
        $this->assertRegExp('#\w+#', $this->component->getCheckJsCode());
    }


    public function testStartFormSubmitTime()
    {
        $this->component->startFormSubmitTime('');
        sleep(2);
        $time = $this->component->calcFormSubmitTime('');
        $this->assertGreaterThanOrEqual(1, $time);
    }


    public function testIsJavascriptEnable()
    {
        Yii::$app->request->setBodyParams(['ct_checkjs' => $this->component->getCheckJsCode()]);
        $this->assertEquals(1, $this->component->isJavascriptEnable());

        Yii::$app->request->setBodyParams([]);
        $this->assertEquals(0, $this->component->isJavascriptEnable());
    }

    public function testCreateRequest()
    {
        $class = new ReflectionClass($this->component);
        $method = $class->getMethod('createRequest');
        $method->setAccessible(true);
        $request = $method->invoke($this->component);
        $this->assertInstanceOf(CleantalkRequest::class, $request);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendRequest()
    {
        $class = new ReflectionClass($this->component);
        $method = $class->getMethod('sendRequest');
        $method->setAccessible(true);
        $method->invoke($this->component, new CleantalkRequest(), 'ololo');
    }

    protected function getSendRequestMock($response)
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
                        $response
                    )
                )
            );
        return $mock;
    }
}