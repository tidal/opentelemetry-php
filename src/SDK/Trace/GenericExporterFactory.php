<?php

declare(strict_types=1);

namespace OpenTelemetry\SDK\Trace;

use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

/**
 * Usage:
 * >>> Pass defaults to the factory.
 * $factory = GenericExporterFactory::create(OpenTelemetry\Contrib\Newrelic\Exporter::class);
 * >>> Set defaults
 * $factory->setDefaults([
 *      'name' => 'foo',
 *      'client' => HttpClientDiscovery::find(),
 *      'request_factory' => Psr17FactoryDiscovery::findRequestFactory(),
 *      'stream_factory' => Psr17FactoryDiscovery::findStreamFactory(),
 * ]);
 * >>> Create exporter with values resolved at runtime.
 * $exporter = $factory->build([
 *      'endpoint_url' => 'http://example.com/foo',
 *      'license_key' => 'abc123',
 * ]);
 */
class GenericExporterFactory
{
    private OptionsResolver $resolver;
    private ReflectionClass $reflectionClass;
    private array $options = [];
    private array $requiredOptions = [];

    /**
     * @param string $exporterClass
     * @param OptionsResolver|null $resolver
     */
    public function __construct(string $exporterClass, ?OptionsResolver $resolver = null)
    {
        $this->init($exporterClass, $resolver ?? new OptionsResolver());
    }

    /**
     * @param string $exporterClass
     * @param OptionsResolver|null $resolver
     * @return self
     */
    public static function create(string $exporterClass, ?OptionsResolver $resolver = null): self
    {
        return new self($exporterClass, $resolver);
    }

    /**
     * @param array $options
     * @throws \ReflectionException
     * @return SpanExporterInterface
     */
    public function build(array $options): SpanExporterInterface
    {
        $options = $this->getOptionsResolver()->resolve($options);
        // make sure arguments are in the correct order;
        $arguments = [];
        foreach ($this->getOptions() as $option) {
            if (!isset($options[$option])) {
                break;
            }
            $arguments[] = $options[$option];
        }

        return $this->getReflectionClass()->newInstanceArgs($arguments);
    }

    /**
     * @param string $option
     * @param mixed $value
     * @return $this
     */
    public function setDefault(string $option, $value): self
    {
        $this->getOptionsResolver()->setDefault($option, $value);

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setDefaults(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->setDefault($option, $value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getRequiredOptions(): array
    {
        return $this->requiredOptions;
    }

    /**
     * @return ReflectionClass
     */
    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflectionClass;
    }

    /**
     * @return string
     */
    public function getExporterClass(): string
    {
        return $this->getReflectionClass()->getName();
    }

    /**
     * @return OptionsResolver
     */
    public function getOptionsResolver(): OptionsResolver
    {
        return $this->resolver;
    }

    private function init(string $exporterClass, OptionsResolver $resolver)
    {
        try {
            $this->setupReflectionClass($exporterClass);
            $this->validateExporterClass($exporterClass);
        } catch (Throwable $t) {
            throw new InvalidArgumentException(
                sprintf('Given class %s is not a valid Span Exporter class', $exporterClass),
                E_ERROR,
                $t
            );
        }
        $this->setOptionsResolver($resolver);
        $this->inspectExporter();
    }

    private function validateExporterClass(string $exporterClass)
    {
        if (!class_exists($exporterClass)) {
            throw new InvalidArgumentException(
                sprintf('Could not find given class %s.', $exporterClass)
            );
        }
        if (!in_array(SpanExporterInterface::class, $this->getReflectionClass()->getInterfaceNames())) {
            throw new InvalidArgumentException(
                sprintf('Class %s  does not implement %s', $exporterClass, SpanExporterInterface::class)
            );
        }
    }

    private function setupReflectionClass(string $exporterClass)
    {
        $this->reflectionClass = new ReflectionClass($exporterClass);
    }

    private function setOptionsResolver(OptionsResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    private function inspectExporter()
    {
        $parameters = $this->getReflectionClass()->getConstructor()->getParameters();

        foreach ($parameters as $parameter) {
            $option = self::camelToSnakeCase($parameter->getName());
            $this->options[$parameter->getPosition()] = $option;
            $this->getOptionsResolver()->define($option);
            if (!$parameter->isOptional()) {
                $this->requiredOptions[] = $option;
            }
            if ($type = $parameter->getType()) {
                $this->getOptionsResolver()
                    ->setAllowedTypes($option, (string) $type);
            }
        }
        $this->getOptionsResolver()->setRequired(
            $this->getRequiredOptions()
        );
    }

    private static function camelToSnakeCase(string $value): string
    {
        return ltrim(
            strtolower(
                preg_replace(
                    '/[A-Z]([A-Z](?![a-z]))*/',
                    '_$0',
                    $value
                )
            ),
            '_'
        );
    }
}
