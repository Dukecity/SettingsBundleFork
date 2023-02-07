<?php

namespace Dmishh\SettingsBundle\Form\Type;

use Dmishh\SettingsBundle\Exception\UnknownConstraintException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Settings management form.
 *
 * @author Dmitriy Scherbina <http://dmishh.com>
 * @author Artem Zhuravlov
 */
class SettingsType extends AbstractType
{
    public function __construct(protected array $settingsConfiguration)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->settingsConfiguration as $name => $configuration) {
            // If setting's value exists in data and setting isn't disabled
            if (\array_key_exists($name, $options['data']) && !\in_array($name, $options['disabled_settings'])) {
                $fieldType = $configuration['type'];
                $fieldOptions = $configuration['options'];
                $fieldOptions['constraints'] = $configuration['constraints'];

                // Validator constraints
                if (!empty($fieldOptions['constraints']) && \is_array($fieldOptions['constraints'])) {
                    $constraints = [];
                    foreach ($fieldOptions['constraints'] as $class => $constraintOptions) {
                        if (class_exists($class)) {
                            $constraints[] = new $class($constraintOptions);
                        } else {
                            throw new UnknownConstraintException($class);
                        }
                    }

                    $fieldOptions['constraints'] = $constraints;
                }

                // Label I18n
                $fieldOptions['label'] = 'labels.'.$name;
                $fieldOptions['translation_domain'] = 'settings';

                // Choices I18n
                if (!empty($fieldOptions['choices'])) {
                    $fieldOptions['choices'] = array_flip(
                        array_map(
                            function ($label) use ($fieldOptions) {
                                return $fieldOptions['label'].'_choices.'.$label;
                            },
                            array_combine($fieldOptions['choices'], $fieldOptions['choices'])
                        )
                    );
                }
                $builder->add($name, $fieldType, $fieldOptions);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'disabled_settings' => [],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'settings_management';
    }
}
