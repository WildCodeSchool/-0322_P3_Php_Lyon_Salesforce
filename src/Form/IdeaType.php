<?php

namespace App\Form;

use App\Entity\Idea;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class IdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Mon idée en quelques mots',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre est trop court.',
                        'maxMessage' => 'Le titre est trop long.',
                    ]),
                ],
            ])
            ->add('perimeter', ChoiceType::class, [
                'label' => 'A qui est destinée mon idée?',
                'choices' => [
                    'A tout le monde' => 'Global',
                    'A mon agence' => 'Agence',
                    'A mon service' => 'Service',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le périmètre est obligatoire.']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le périmètre est trop court.',
                        'maxMessage' => 'Le périmètre est trop long.',
                    ]),
                ],
            ])
            ->add('content', CKEditorType::class, [
                'label' => 'Expliquer mon idée',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contenu de l\'idée est obligatoire.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Développez d\'avantage votre idée.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Idea::class,
        ]);
    }
}
