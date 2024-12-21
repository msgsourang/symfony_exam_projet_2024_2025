<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApprovisionnementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('article', ChoiceType::class, [
                'choices' => $options['articles'],
                'choice_label' => function ($article) {
                    return "{$article->getNom()} (Stock: {$article->getQteStock()}, Prix: {$article->getPrix()} FCFA)";
                },
                'choice_value' => 'id', 
                'placeholder' => 'Sélectionner un article',
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['min' => 1],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, 
            'articles' => [], 
        ]);
    }
}
