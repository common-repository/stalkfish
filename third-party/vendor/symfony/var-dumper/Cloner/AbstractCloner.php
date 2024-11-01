<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Stalkfish\Dependencies\Symfony\Component\VarDumper\Cloner;

use Stalkfish\Dependencies\Symfony\Component\VarDumper\Caster\Caster;
use Stalkfish\Dependencies\Symfony\Component\VarDumper\Exception\ThrowingCasterException;
/**
 * AbstractCloner implements a generic caster mechanism for objects and resources.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
abstract class AbstractCloner implements ClonerInterface
{
    public static $defaultCasters = ['__PHP_Incomplete_Class' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\Caster', 'castPhpIncompleteClass'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\CutStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'castStub'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\CutArrayStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'castCutArray'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ConstStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'castStub'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\EnumStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'castEnum'], 'Fiber' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\FiberCaster', 'castFiber'], 'Closure' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castClosure'], 'Generator' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castGenerator'], 'ReflectionType' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castType'], 'ReflectionAttribute' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castAttribute'], 'ReflectionGenerator' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castReflectionGenerator'], 'ReflectionClass' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castClass'], 'ReflectionClassConstant' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castClassConstant'], 'ReflectionFunctionAbstract' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castFunctionAbstract'], 'ReflectionMethod' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castMethod'], 'ReflectionParameter' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castParameter'], 'ReflectionProperty' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castProperty'], 'ReflectionReference' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castReference'], 'ReflectionExtension' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castExtension'], 'ReflectionZendExtension' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ReflectionCaster', 'castZendExtension'], 'Stalkfish\\Dependencies\\Doctrine\\Common\\Persistence\\ObjectManager' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Doctrine\\Common\\Proxy\\Proxy' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DoctrineCaster', 'castCommonProxy'], 'Stalkfish\\Dependencies\\Doctrine\\ORM\\Proxy\\Proxy' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DoctrineCaster', 'castOrmProxy'], 'Stalkfish\\Dependencies\\Doctrine\\ORM\\PersistentCollection' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DoctrineCaster', 'castPersistentCollection'], 'Stalkfish\\Dependencies\\Doctrine\\Persistence\\ObjectManager' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'DOMException' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castException'], 'DOMStringList' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castLength'], 'DOMNameList' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castLength'], 'DOMImplementation' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castImplementation'], 'DOMImplementationList' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castLength'], 'DOMNode' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castNode'], 'DOMNameSpaceNode' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castNameSpaceNode'], 'DOMDocument' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castDocument'], 'DOMNodeList' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castLength'], 'DOMNamedNodeMap' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castLength'], 'DOMCharacterData' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castCharacterData'], 'DOMAttr' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castAttr'], 'DOMElement' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castElement'], 'DOMText' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castText'], 'DOMTypeinfo' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castTypeinfo'], 'DOMDomError' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castDomError'], 'DOMLocator' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castLocator'], 'DOMDocumentType' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castDocumentType'], 'DOMNotation' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castNotation'], 'DOMEntity' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castEntity'], 'DOMProcessingInstruction' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castProcessingInstruction'], 'DOMXPath' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DOMCaster', 'castXPath'], 'XMLReader' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\XmlReaderCaster', 'castXmlReader'], 'ErrorException' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castErrorException'], 'Exception' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castException'], 'Error' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castError'], 'Stalkfish\\Dependencies\\Symfony\\Bridge\\Monolog\\Logger' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Symfony\\Component\\DependencyInjection\\ContainerInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Symfony\\Component\\EventDispatcher\\EventDispatcherInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpClient\\AmpHttpClient' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castHttpClient'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpClient\\CurlHttpClient' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castHttpClient'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpClient\\NativeHttpClient' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castHttpClient'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpClient\\Response\\AmpResponse' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castHttpClientResponse'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpClient\\Response\\CurlResponse' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castHttpClientResponse'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpClient\\Response\\NativeResponse' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castHttpClientResponse'], 'Stalkfish\\Dependencies\\Symfony\\Component\\HttpFoundation\\Request' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castRequest'], 'Stalkfish\\Dependencies\\Symfony\\Component\\Uid\\Ulid' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castUlid'], 'Stalkfish\\Dependencies\\Symfony\\Component\\Uid\\Uuid' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SymfonyCaster', 'castUuid'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Exception\\ThrowingCasterException' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castThrowingCasterException'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\TraceStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castTraceStub'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\FrameStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castFrameStub'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Cloner\\AbstractCloner' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Symfony\\Component\\ErrorHandler\\Exception\\SilencedErrorContext' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ExceptionCaster', 'castSilencedErrorContext'], 'Stalkfish\\Dependencies\\Imagine\\Image\\ImageInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ImagineCaster', 'castImage'], 'Stalkfish\\Dependencies\\Ramsey\\Uuid\\UuidInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\UuidCaster', 'castRamseyUuid'], 'Stalkfish\\Dependencies\\ProxyManager\\Proxy\\ProxyInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ProxyManagerCaster', 'castProxy'], 'PHPUnit_Framework_MockObject_MockObject' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\PHPUnit\\Framework\\MockObject\\MockObject' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\PHPUnit\\Framework\\MockObject\\Stub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Prophecy\\Prophecy\\ProphecySubjectInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'Stalkfish\\Dependencies\\Mockery\\MockInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\StubCaster', 'cutInternals'], 'PDO' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\PdoCaster', 'castPdo'], 'PDOStatement' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\PdoCaster', 'castPdoStatement'], 'AMQPConnection' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\AmqpCaster', 'castConnection'], 'AMQPChannel' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\AmqpCaster', 'castChannel'], 'AMQPQueue' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\AmqpCaster', 'castQueue'], 'AMQPExchange' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\AmqpCaster', 'castExchange'], 'AMQPEnvelope' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\AmqpCaster', 'castEnvelope'], 'ArrayObject' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castArrayObject'], 'ArrayIterator' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castArrayIterator'], 'SplDoublyLinkedList' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castDoublyLinkedList'], 'SplFileInfo' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castFileInfo'], 'SplFileObject' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castFileObject'], 'SplHeap' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castHeap'], 'SplObjectStorage' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castObjectStorage'], 'SplPriorityQueue' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castHeap'], 'OuterIterator' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castOuterIterator'], 'WeakReference' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\SplCaster', 'castWeakReference'], 'Redis' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RedisCaster', 'castRedis'], 'RedisArray' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RedisCaster', 'castRedisArray'], 'RedisCluster' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RedisCaster', 'castRedisCluster'], 'DateTimeInterface' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DateCaster', 'castDateTime'], 'DateInterval' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DateCaster', 'castInterval'], 'DateTimeZone' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DateCaster', 'castTimeZone'], 'DatePeriod' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DateCaster', 'castPeriod'], 'GMP' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\GmpCaster', 'castGmp'], 'MessageFormatter' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\IntlCaster', 'castMessageFormatter'], 'NumberFormatter' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\IntlCaster', 'castNumberFormatter'], 'IntlTimeZone' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\IntlCaster', 'castIntlTimeZone'], 'IntlCalendar' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\IntlCaster', 'castIntlCalendar'], 'IntlDateFormatter' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\IntlCaster', 'castIntlDateFormatter'], 'Memcached' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\MemcachedCaster', 'castMemcached'], 'Stalkfish\\Dependencies\\Ds\\Collection' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DsCaster', 'castCollection'], 'Stalkfish\\Dependencies\\Ds\\Map' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DsCaster', 'castMap'], 'Stalkfish\\Dependencies\\Ds\\Pair' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DsCaster', 'castPair'], 'Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DsPairStub' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\DsCaster', 'castPairStub'], 'mysqli_driver' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\MysqliCaster', 'castMysqliDriver'], 'CurlHandle' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castCurl'], ':curl' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castCurl'], ':dba' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castDba'], ':dba persistent' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castDba'], 'GdImage' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castGd'], ':gd' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castGd'], ':mysql link' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castMysqlLink'], ':pgsql large object' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\PgSqlCaster', 'castLargeObject'], ':pgsql link' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\PgSqlCaster', 'castLink'], ':pgsql link persistent' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\PgSqlCaster', 'castLink'], ':pgsql result' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\PgSqlCaster', 'castResult'], ':process' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castProcess'], ':stream' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castStream'], 'OpenSSLCertificate' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castOpensslX509'], ':OpenSSL X.509' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castOpensslX509'], ':persistent stream' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castStream'], ':stream-context' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\ResourceCaster', 'castStreamContext'], 'XmlParser' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\XmlResourceCaster', 'castXml'], ':xml' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\XmlResourceCaster', 'castXml'], 'RdKafka' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castRdKafka'], 'Stalkfish\\Dependencies\\RdKafka\\Conf' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castConf'], 'Stalkfish\\Dependencies\\RdKafka\\KafkaConsumer' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castKafkaConsumer'], 'Stalkfish\\Dependencies\\RdKafka\\Metadata\\Broker' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castBrokerMetadata'], 'Stalkfish\\Dependencies\\RdKafka\\Metadata\\Collection' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castCollectionMetadata'], 'Stalkfish\\Dependencies\\RdKafka\\Metadata\\Partition' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castPartitionMetadata'], 'Stalkfish\\Dependencies\\RdKafka\\Metadata\\Topic' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castTopicMetadata'], 'Stalkfish\\Dependencies\\RdKafka\\Message' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castMessage'], 'Stalkfish\\Dependencies\\RdKafka\\Topic' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castTopic'], 'Stalkfish\\Dependencies\\RdKafka\\TopicPartition' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castTopicPartition'], 'Stalkfish\\Dependencies\\RdKafka\\TopicConf' => ['Stalkfish\\Dependencies\\Symfony\\Component\\VarDumper\\Caster\\RdKafkaCaster', 'castTopicConf']];
    protected $maxItems = 2500;
    protected $maxString = -1;
    protected $minDepth = 1;
    /**
     * @var array<string, list<callable>>
     */
    private $casters = [];
    /**
     * @var callable|null
     */
    private $prevErrorHandler;
    private $classInfo = [];
    private $filter = 0;
    /**
     * @param callable[]|null $casters A map of casters
     *
     * @see addCasters
     */
    public function __construct(array $casters = null)
    {
        if (null === $casters) {
            $casters = static::$defaultCasters;
        }
        $this->addCasters($casters);
    }
    /**
     * Adds casters for resources and objects.
     *
     * Maps resources or objects types to a callback.
     * Types are in the key, with a callable caster for value.
     * Resource types are to be prefixed with a `:`,
     * see e.g. static::$defaultCasters.
     *
     * @param callable[] $casters A map of casters
     */
    public function addCasters(array $casters)
    {
        foreach ($casters as $type => $callback) {
            $this->casters[$type][] = $callback;
        }
    }
    /**
     * Sets the maximum number of items to clone past the minimum depth in nested structures.
     */
    public function setMaxItems(int $maxItems)
    {
        $this->maxItems = $maxItems;
    }
    /**
     * Sets the maximum cloned length for strings.
     */
    public function setMaxString(int $maxString)
    {
        $this->maxString = $maxString;
    }
    /**
     * Sets the minimum tree depth where we are guaranteed to clone all the items.  After this
     * depth is reached, only setMaxItems items will be cloned.
     */
    public function setMinDepth(int $minDepth)
    {
        $this->minDepth = $minDepth;
    }
    /**
     * Clones a PHP variable.
     *
     * @param mixed $var    Any PHP variable
     * @param int   $filter A bit field of Caster::EXCLUDE_* constants
     *
     * @return Data
     */
    public function cloneVar($var, int $filter = 0)
    {
        $this->prevErrorHandler = \set_error_handler(function ($type, $msg, $file, $line, $context = []) {
            if (\E_RECOVERABLE_ERROR === $type || \E_USER_ERROR === $type) {
                // Cloner never dies
                throw new \ErrorException($msg, 0, $type, $file, $line);
            }
            if ($this->prevErrorHandler) {
                return ($this->prevErrorHandler)($type, $msg, $file, $line, $context);
            }
            return \false;
        });
        $this->filter = $filter;
        if ($gc = \gc_enabled()) {
            \gc_disable();
        }
        try {
            return new Data($this->doClone($var));
        } finally {
            if ($gc) {
                \gc_enable();
            }
            \restore_error_handler();
            $this->prevErrorHandler = null;
        }
    }
    /**
     * Effectively clones the PHP variable.
     *
     * @param mixed $var Any PHP variable
     *
     * @return array
     */
    protected abstract function doClone($var);
    /**
     * Casts an object to an array representation.
     *
     * @param bool $isNested True if the object is nested in the dumped structure
     *
     * @return array
     */
    protected function castObject(Stub $stub, bool $isNested)
    {
        $obj = $stub->value;
        $class = $stub->class;
        if (\PHP_VERSION_ID < 80000 ? "\x00" === ($class[15] ?? null) : \str_contains($class, "@anonymous\x00")) {
            $stub->class = \get_debug_type($obj);
        }
        if (isset($this->classInfo[$class])) {
            [$i, $parents, $hasDebugInfo, $fileInfo] = $this->classInfo[$class];
        } else {
            $i = 2;
            $parents = [$class];
            $hasDebugInfo = \method_exists($class, '__debugInfo');
            foreach (\class_parents($class) as $p) {
                $parents[] = $p;
                ++$i;
            }
            foreach (\class_implements($class) as $p) {
                $parents[] = $p;
                ++$i;
            }
            $parents[] = '*';
            $r = new \ReflectionClass($class);
            $fileInfo = $r->isInternal() || $r->isSubclassOf(Stub::class) ? [] : ['file' => $r->getFileName(), 'line' => $r->getStartLine()];
            $this->classInfo[$class] = [$i, $parents, $hasDebugInfo, $fileInfo];
        }
        $stub->attr += $fileInfo;
        $a = Caster::castObject($obj, $class, $hasDebugInfo, $stub->class);
        try {
            while ($i--) {
                if (!empty($this->casters[$p = $parents[$i]])) {
                    foreach ($this->casters[$p] as $callback) {
                        $a = $callback($obj, $a, $stub, $isNested, $this->filter);
                    }
                }
            }
        } catch (\Exception $e) {
            $a = [(Stub::TYPE_OBJECT === $stub->type ? Caster::PREFIX_VIRTUAL : '') . '⚠' => new ThrowingCasterException($e)] + $a;
        }
        return $a;
    }
    /**
     * Casts a resource to an array representation.
     *
     * @param bool $isNested True if the object is nested in the dumped structure
     *
     * @return array
     */
    protected function castResource(Stub $stub, bool $isNested)
    {
        $a = [];
        $res = $stub->value;
        $type = $stub->class;
        try {
            if (!empty($this->casters[':' . $type])) {
                foreach ($this->casters[':' . $type] as $callback) {
                    $a = $callback($res, $a, $stub, $isNested, $this->filter);
                }
            }
        } catch (\Exception $e) {
            $a = [(Stub::TYPE_OBJECT === $stub->type ? Caster::PREFIX_VIRTUAL : '') . '⚠' => new ThrowingCasterException($e)] + $a;
        }
        return $a;
    }
}
