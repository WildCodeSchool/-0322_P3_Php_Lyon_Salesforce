<?php

namespace App\Form;

use App\Entity\Office;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email obligatoire']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Email non valide',
                        'maxMessage' => 'Email trop long',
                    ]),
                ],
            ])
            ->add('slackId', TextType::class, [
                'label' => 'ID Slack'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Prénom obligatoire']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Prénom trop long',
                    ]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Nom obligatoire']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Nom trop long',
                    ]),
                ],
            ])
            ->add('picture', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PNG, JPEG ou GIF',
                    ])
                ],
            ])
            ->add('department', TextType::class, [
                'label' => 'Service',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Service obligatoire']),
                    ]
            ])
            ->add('workplace', EntityType::class, [
                'class' => Office::class,
                'label' => 'Agence',
                'choice_label' => 'location',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Lieu de travail obligatoire']),
                    ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
