<?php

namespace adapt\tools\di;

use adapt\tools\common\arrayHelper\ArrayHelper;
use adapt\tools\exceptions\InvalidConfigException;
use adapt\tools\exceptions\NotInstantiableException;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Container
{
    /**
     * @var array singleton objects indexed by their types
     */
    private $_singletons = [];
    /**
     * @var array object definitions indexed by their types
     */
    private $_definitions = [];
    /**
     * @var array constructor parameters indexed by object types
     */
    private $_params = [];
    /**
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private $_reflections = [];
    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $_dependencies = [];

    public function __construct($config = [])
    {
        if (isset($config['singletons'])) {
            $this->setSingletons($config['singletons']);
        }
        if (isset($config['definitions'])) {
            $this->setDefinitions($config['definitions']);
        }
    }

    /**
     * Returns an instance of the requested class.
     *
     * You may provide constructor parameters (`$params`) and object configurations (`$config`)
     * that will be used during the creation of the instance.
     *
     * If the class implements [[\yii\base\Configurable]], the `$config` parameter will be passed as the last
     * parameter to the class constructor; Otherwise, the configuration will be applied *after* the object is
     * instantiated.
     *
     * Note that if the class is declared to be singleton by calling [[setSingleton()]],
     * the same instance of the class will be returned each time this method is called.
     * In this case, the constructor parameters and object configurations will be used
     * only if the class is instantiated the first time.
     *
     * @param string $class  the class name or an alias name (e.g. `foo`) that was previously registered via [[set()]]
     *                       or [[setSingleton()]].
     * @param array  $params a list of constructor parameter values. The parameters should be provided in the order
     *                       they appear in the constructor declaration. If you want to skip some parameters, you
     *                       should index the remaining ones with the integers that represent their positions in the
     *                       constructor parameter list.
     * @param array  $config a list of name-value pairs that will be used to initialize the object properties.
     *
     * @return object an instance of the requested class.
     * @throws InvalidConfigException if the class cannot be recognized or correspond to an invalid definition
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     */
    public function get($class, $config = [])
    {
        if (isset($this->_singletons[$class])) {
            // singleton
            return $this->_singletons[$class];
        } elseif (!isset($this->_definitions[$class])) {
            return $this->build($class, $config);
        }

        $definition = $this->_definitions[$class];

        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $config));
            $object = $definition($this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);

            if ($concrete === $class) {
                $object = $this->build($class, $config);
            } else {
                $object = $this->get($concrete, $config);
            }
        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }

        return $object;
    }

    /**
     * Registers a class definition with this container.
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->set('yii\db\Connection');
     *
     * // register an interface
     * // When a class depends on the interface, the corresponding class
     * // will be instantiated as the dependent object
     * $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * $container->set('foo', 'yii\db\Connection');
     *
     * // register a class with configuration. The configuration
     * // will be applied when the class is instantiated by get()
     * $container->set('yii\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register an alias name with class configuration
     * // In this case, a "class" element is required to specify the class
     * $container->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a PHP callable
     * // The callable will be executed when $container->get('db') is called
     * $container->set('db', function ($container, $params, $config) {
     *     return new \yii\db\Connection($config);
     * });
     * ```
     *
     * If a class definition with the same name already exists, it will be overwritten with the new one.
     * You may use [[has()]] to check if a class definition already exists.
     *
     * @param string $class      class name, interface name or alias name
     * @param mixed  $definition the definition associated with `$class`. It can be one of the following:
     *
     * - a PHP callable: The callable will be executed when [[get()]] is invoked. The signature of the callable
     *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
     *   parameters, `$config` the object configuration, and `$container` the container object. The return value
     *   of the callable will be returned by [[get()]] as the object instance requested.
     * - a configuration array: the array contains name-value pairs that will be used to initialize the property
     *   values of the newly created object when [[get()]] is called. The `class` element stands for the
     *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
     * - a string: a class name, an interface name or an alias name.
     * @param array  $params     the list of constructor parameters. The parameters will be passed to the class
     *                           constructor when [[get()]] is called.
     *
     * @return $this the container itself
     */
    public function set($class, $definition = [], array $params = [])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        unset($this->_singletons[$class]);
        return $this;
    }

    /**
     * Registers a class definition with this container and marks the class as a singleton class.
     *
     * This method is similar to [[set()]] except that classes registered via this method will only have one
     * instance. Each time [[get()]] is called, the same instance of the specified class will be returned.
     *
     * @param string $class      class name, interface name or alias name
     * @param mixed  $definition the definition associated with `$class`. See [[set()]] for more details.
     * @param array  $params     the list of constructor parameters. The parameters will be passed to the class
     *                           constructor when [[get()]] is called.
     *
     * @return $this the container itself
     * @see set()
     */
    public function setSingleton($class, $definition = [], array $params = [])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        $this->_singletons[$class] = null;
        return $this;
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     *
     * @param string $class class name, interface name or alias name
     *
     * @return bool whether the container has the definition of the specified name..
     * @see set()
     */
    public function has($class)
    {
        return isset($this->_definitions[$class]);
    }

    /**
     * Returns a value indicating whether the given name corresponds to a registered singleton.
     *
     * @param string $class         class name, interface name or alias name
     * @param bool   $checkInstance whether to check if the singleton has been instantiated.
     *
     * @return bool whether the given name corresponds to a registered singleton. If `$checkInstance` is true,
     * the method should return a value indicating whether the singleton has been instantiated.
     */
    public function hasSingleton($class, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
    }

    /**
     * Removes the definition for the specified name.
     *
     * @param string $class class name, interface name or alias name
     */
    public function clear($class)
    {
        unset($this->_definitions[$class], $this->_singletons[$class]);
    }

    /**
     * Normalizes the class definition.
     *
     * @param string                $class      class name
     * @param string|array|callable $definition the class definition
     *
     * @return array the normalized class definition
     * @throws InvalidConfigException if the definition is invalid.
     */
    protected function normalizeDefinition($class, $definition)
    {
        if (empty($definition)) {
            return ['class' => $class];
        } elseif (is_string($definition)) {
            return ['class' => $definition];
        } elseif (is_callable($definition, true) || is_object($definition)) {
            return $definition;
        } elseif (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException('A class definition requires a "class" member.');
                }
            }

            return $definition;
        }

        throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
    }

    /**
     * Returns the list of the object definitions or the loaded shared objects.
     *
     * @return array the list of the object definitions or the loaded shared objects (type or ID => definition or
     *               instance).
     */
    public function getDefinitions()
    {
        return $this->_definitions;
    }

    /**
     * Creates an instance of the specified class.
     * This method will resolve dependencies of the specified class, instantiate them, and inject
     * them into the new instance of the specified class.
     *
     * @param string $class  the class name
     * @param array  $params constructor parameters
     * @param array  $config configurations to be applied to the new instance
     *
     * @return object the newly created instance of the specified class
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     */
    protected function build($class, $config)
    {
        /* @var $reflection ReflectionClass */
        list($reflection, $dependencies) = $this->getDependencies($class);

        foreach ($dependencies as $key => $dependency) {
            if (isset($config[$dependency->getName()])) {
                $dependencies[$key] = $config[$dependency->getName()];
            } elseif (isset($config[$key])) {
                $dependencies[$key] = $config[$key];
            }
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Merges the user-specified constructor parameters with the ones registered via [[set()]].
     *
     * @param string $class  class name, interface name or alias name
     * @param array  $params the constructor parameters
     *
     * @return array the merged parameters
     */
    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        }

        $ps = $this->_params[$class];
        foreach ($params as $index => $value) {
            $ps[$index] = $value;
        }

        return $ps;
    }

    /**
     * Returns the dependencies of the specified class.
     *
     * @param string $class class name, interface name or alias name
     *
     * @return array the dependencies of the specified class.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function getDependencies($class)
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new InvalidConfigException('Failed to instantiate component or class "' . $class . '".', 0, $e);
        }

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $c = $param->getClass();
                $dependency = Instance::of($c === null ? null : $c->getName(), $param->getName());

                if (version_compare(PHP_VERSION, '5.6.0', '>=') && $param->isVariadic()) {
                    break;
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependency->setDefault($param->getDefaultValue());
                }

                $dependencies[] = $dependency;
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     *
     * @param array           $dependencies the dependencies
     * @param ReflectionClass $reflection   the class reflection associated with the dependencies
     *
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    try {
                        $dependencies[$index] = $this->get($dependency->id);
                    } catch (NotInstantiableException $e) {
                        if ($dependency->hasDefault()) {
                            $dependencies[$index] = $dependency->getDefault();
                        } else {
                            throw $e;
                        }
                    }
                } elseif ($dependency->hasDefault()) {
                    $dependencies[$index] = $dependency->getDefault();
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }

        return $dependencies;
    }

    /**
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling function using named parameters.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \yii\i18n\Formatter $formatter) {
     *    // ...
     * }
     * Yii::$container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
     * by the DI container as the second param to the callable.
     *
     * @param callable $callback callable to be invoked.
     * @param array    $params   The array of parameters for the function.
     *                           This can be either a list of parameters, or an associative array representing named
     *                           function parameters.
     *
     * @return mixed the callback return value.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @since 2.0.7
     */
    public function invoke(callable $callback, $params = [])
    {
        return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
    }

    /**
     * Resolve dependencies for a function.
     *
     * This method can be used to implement similar functionality as provided by [[invoke()]] in other
     * components.
     *
     * @param callable $callback callable to be invoked.
     * @param array    $params   The array of parameters for the function, can be either numeric or associative.
     *
     * @return array The resolved dependencies.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @since 2.0.7
     */
    public function resolveCallableDependencies(callable $callback, $params = [])
    {
        if (is_array($callback)) {
            $reflection = new ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_object($callback) && !$callback instanceof Closure) {
            $reflection = new ReflectionMethod($callback, '__invoke');
        } else {
            $reflection = new ReflectionFunction($callback);
        }

        $args = [];

        $associative = ArrayHelper::isAssociative($params);

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (($class = $param->getClass()) !== null) {
                $className = $class->getName();
                if (version_compare(PHP_VERSION, '5.6.0', '>=') && $param->isVariadic()) {
                    $args = array_merge($args, array_values($params));
                    break;
                } elseif ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
                    $args[] = array_shift($params);
                } else {
                    // If the argument is optional we catch not instantiable exceptions
                    try {
                        $args[] = $this->get($className);
                    } catch (NotInstantiableException $e) {
                        if ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }
                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }

        return $args;
    }

    /**
     * Registers class definitions within this container.
     *
     * @param array $definitions array of definitions. There are two allowed formats of array.
     *                           The first format:
     *                           - key: class name, interface name or alias name. The key will be passed to the
     *                           [[set()]] method as a first argument `$class`.
     *                           - value: the definition associated with `$class`. Possible values are described in
     *                           [[set()]] documentation for the `$definition` parameter. Will be passed to the
     *                           [[set()]] method as the second argument `$definition`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'yii\web\Request' => 'app\components\Request',
     *     'yii\web\Response' => [
     *         'class' => 'app\components\Response',
     *         'format' => 'json'
     *     ],
     *     'foo\Bar' => function () {
     *         $qux = new Qux;
     *         $foo = new Foo($qux);
     *         return new Bar($foo);
     *     }
     * ]);
     * ```
     *
     * The second format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: array of two elements. The first element will be passed the [[set()]] method as the
     *    second argument `$definition`, the second one — as `$params`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'foo\Bar' => [
     *          ['class' => 'app\Bar'],
     *          [Instance::of('baz')]
     *      ]
     * ]);
     * ```
     *
     * @see   set() to know more about possible values of definitions
     * @since 2.0.11
     */
    public function setDefinitions(array $definitions)
    {
        foreach ($definitions as $class => $definition) {
            if (is_array($definition) && count($definition) === 2 && array_values($definition) === $definition) {
                $this->set($class, $definition[0], $definition[1]);
                continue;
            }

            $this->set($class, $definition);
        }
    }

    /**
     * Registers class definitions as singletons within this container by calling [[setSingleton()]].
     *
     * @param array $singletons array of singleton definitions. See [[setDefinitions()]]
     *                          for allowed formats of array.
     *
     * @see   setDefinitions() for allowed formats of $singletons parameter
     * @see   setSingleton() to know more about possible values of definitions
     * @since 2.0.11
     */
    public function setSingletons(array $singletons)
    {
        foreach ($singletons as $class => $definition) {
            if (is_array($definition) && count($definition) === 2 && array_values($definition) === $definition) {
                $this->setSingleton($class, $definition[0], $definition[1]);
                continue;
            }

            $this->setSingleton($class, $definition);
        }
    }
}
