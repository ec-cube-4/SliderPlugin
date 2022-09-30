<?php

namespace Plugin\SliderPlugin42\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Category")
 */
trait CategoryTrait
{
    /**
     * @var SilderCategoryImage[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\SliderPlugin42\Entity\SilderCategoryImage", mappedBy="Category", cascade={"persist", "remove"})
     * @ORM\OrderBy({
     *     "sort_no"="ASC"
     * })
     */
    private $SilderCategoryImages;

    /**
     * @return SilderCategoryImage[]|Collection
     */
    public function getSilderCategoryImages()
    {
        if (null === $this->SilderCategoryImages) {
            $this->SilderCategoryImages = new ArrayCollection();
        }

        return $this->SilderCategoryImages;
    }

    /**
     * @param SilderCategoryImage $SilderCategoryImage
     */
    public function addSilderCategoryImage(SilderCategoryImage $SilderCategoryImage)
    {
        if (null === $this->SilderCategoryImages) {
            $this->SilderCategoryImages = new ArrayCollection();
        }

        $this->SilderCategoryImages[] = $SilderCategoryImage;
    }

    /**
     * @param SilderCategoryImage $SilderCategoryImage
     *
     * @return bool
     */
    public function removeSilderCategoryImage(SilderCategoryImage $SilderCategoryImage)
    {
        if (null === $this->SilderCategoryImages) {
            $this->SilderCategoryImages = new ArrayCollection();
        }

        return $this->SilderCategoryImages->removeElement($SilderCategoryImage);
    }
}
