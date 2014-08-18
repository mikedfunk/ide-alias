<?php
/**
 * ide alias - a laravel artisan command
 *
 * @package IdeAlias
 * @license MIT License <http://opensource.org/licenses/mit-license.html>
 */
namespace TerrenceHoward\IdeAlias\Commands;

use Config;
use Illuminate\Console\Command;

/**
 * IdeAliasCommand
 *
 * @uses Command
 * @author Terrence Howard
 */
class IdeAliasCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ide:alias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $aliases = Config::get('app.aliases');
        $path = 'app/storage/ide';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        foreach ($aliases as $alias => $reference) {
            $class = null;
            $facade = is_subclass_of($alias, 'Illuminate\Support\Facades\Facade');

            if ($facade) {
                try {
                    $object = $reference::getFacadeRoot();
                    $class = get_class($object);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            } else {
                $class = $reference;
            }

            if (class_exists($class)) {
                $this->info('Writing ' . $alias . ', which is a ' . ($facade ? 'facade' : 'child') . ' of ' . $class);

                $file = fopen($path . '/' . $alias . '.php', 'w');

                $r = new ReflectionClass($class);
                $this->startFile($file, $alias, $class, $r);

                if ($facade) {
                    $this->writeClass($file, $reference, new ReflectionClass($reference));
                }

                $this->writeClass($file, $class, $r);

                $this->endFile($file);

                fclose($file);
            } else {
                $this->error('Could not write ' . $alias . '.');
            }
        }
    }

    /**
     * startFile
     *
     * @param array $file
     * @param string $alias
     * @param string $class
     * @param ReflectionClass $r
     * @return void
     */
    public function startFile($file, $alias, $class, ReflectionClass $r)
    {
        fwrite($file, "<?php \n\n");
        $this->writeClassDocComment($file, $r->getDocComment(), $class);
        fwrite($file, "class {$alias} \n{\n");
    }

    /**
     * endFile
     *
     * @param array $file
     * @return void
     */
    public function endFile($file)
    {
        fwrite($file, "}\n");
    }

    /**
     * @see
     */
    public function writeClass($file, $class, ReflectionClass $r)
    {
        $this->writeConstants($file, $class, $r->getConstants());
        $this->writeProperties(
            $file,
            $class,
            $r->getProperties(
                ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_STATIC
            ),
            $r->getDefaultProperties()
        );
        $this->writeMethods($file, $class, $r->getMethods(ReflectionMethod::IS_PUBLIC));
    }

    /**
     * writeClassDocComment
     *
     * @param array $file
     * @param string $comment
     * @param string $class
     * @return void
     */
    public function writeClassDocComment($file, $comment, $class)
    {
        if (!$comment) {
            $comment = "/**\n */";
        }

        $comment = str_replace(' */', " * @see {$class}\n */", $comment);
        fwrite($file, $comment . "\n");
    }

    /**
     * writeDocComment
     *
     * @param array $file
     * @param string $comment
     * @param string $class
     * @param string $name
     * @return void
     */
    public function writeDocComment($file, $comment, $class, $name)
    {
        if (!$comment) {
            $comment = "/**\n     */";
        }

        $comment = str_replace('*/', "*\n     * @see {$class}::{$name}\n     */", $comment);
        fwrite($file, '    ' . $comment . "\n");
    }

    /**
     * writeConstants
     *
     * @param array $file
     * @param string $class
     * @param array $constants
     * @return void
     */
    public function writeConstants($file, $class, $constants)
    {
        foreach ($constants as $key => $value) {
            $this->writeConstant($file, $class, $key, $value);
        }
    }

    /**
     * writeConstant
     *
     * @param array $file
     * @param string $class
     * @param string $key
     * @param string $value
     * @return void
     */
    public function writeConstant($file, $class, $key, $value)
    {
        $this->writeDocComment($file, null, $class, $key);
        fwrite($file, "    const " . $key . " = " . var_export(json_decode(json_encode($value), true), true) . ";\n\n");
    }

    /**
     * writeProperties
     *
     * @param string $file
     * @param string $class
     * @param array $properties
     * @param array $defaults
     * @return void
     */
    public function writeProperties($file, $class, $properties, $defaults)
    {
        foreach ($properties as $property) {
            $this->writeProperty($file, $class, $property, $defaults);
        }
    }

    /**
     * writeProperty
     *
     * @param array $file
     * @param string $class
     * @param ReflectionProperty $property
     * @param array $defaults
     * @return void
     */
    public function writeProperty($file, $class, ReflectionProperty $property, $defaults)
    {
        $this->writeDocComment($file, $property->getDocComment(), $class, $property->getName());
        fwrite(
            $file,
            "    public static $" . $property->getName() . " = "
            . var_export(json_decode(json_encode(
                $defaults[$property->getName()]
            ), true), true) . ";\n\n"
        );
    }

    /**
     * writeMethods
     *
     * @param array $file
     * @param string $class
     * @param array $methods
     * @return void
     */
    public function writeMethods($file, $class, $methods)
    {
        foreach ($methods as $method) {
            $this->writeMethod($file, $class, $method);
        }
    }

    /**
     * writeMethod
     *
     * @param array $file
     * @param string $class
     * @param ReflectionMethod $method
     * @return void
     */
    public function writeMethod($file, $class, ReflectionMethod $method)
    {
        $this->writeDocComment($file, $method->getDocComment(), $class, $method->getName());
        fwrite($file, "    public static function " . $method->getName() . "(");
        $this->writeParameters($file, $method->getParameters());
        fwrite($file, ")\n    {\n");
        fwrite($file, "    }\n\n");
    }

    /**
     * writeParameters
     *
     * @param array $file
     * @param array $parameters
     * @return void
     */
    public function writeParameters($file, $parameters)
    {
        $array = array();

        foreach ($parameters as $parameter) {
            $array[] = $this->writeParameter($file, $parameter);
        }

        fwrite($file, implode(', ', $array));
    }

    /**
     * writeParameter
     *
     * @param array $file
     * @param ReflectionParameter $parameter
     * @return void
     */
    public function writeParameter($file, ReflectionParameter $parameter)
    {
        $class = $parameter->getClass();

        $c = '';

        if ($class) {
            $c = $class->getName();
        }

        $default = false;
        $d = null;

        if ($parameter->isDefaultValueAvailable()) {
            $d = $parameter->getDefaultValue();
            $default = true;
        }

        return trim($c . ' $' . $parameter->getName() . ($default ? ' = '
            . var_export(json_decode(json_encode($d), true), true) : ''));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        //array('example', InputArgument::REQUIRED, 'An example argument.'),
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        //array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
        return array();
    }
}
