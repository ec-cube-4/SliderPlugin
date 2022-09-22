<?php

namespace Plugin\SliderPlugin4;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\TemplateEvent;
use Eccube\Common\EccubeConfig;
use Plugin\SliderPlugin4\Repository\SilderCategoryImageRepository;

class SliderEvent implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var SilderCategoryImageRepository
     */
    protected $silderCategoryImageRepository;


    /**
     * SliderEvent constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        SilderCategoryImageRepository $silderCategoryImageRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->silderCategoryImageRepository = $silderCategoryImageRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'index.twig' => 'onTemplateTop',
            'Product/list.twig' => 'onTemplateList'
        ];
    }


    /**
     *
     * @param TemplateEvent $event
     */
    public function onTemplateTop(TemplateEvent $event)
    {
        $SilderImages = $this->silderCategoryImageRepository->findBy(['Category' => null], ['sort_no' => 'ASC']);
        $event->setParameter('SilderImages', $SilderImages);
        $event->addSnippet('@SliderPlugin4/default/slider_top.twig');
    }

    /**
     *
     * @param TemplateEvent $event
     */
    public function onTemplateList(TemplateEvent $event)
    {
        if (!$event->hasParameter('Category')) {
            return;
        }

        $Category = $event->getParameter('Category');
        $SilderImages = $this->silderCategoryImageRepository->findBy(['Category' => $Category], ['sort_no' => 'ASC']);
        $event->setParameter('SilderImages', $SilderImages);
    }
}
