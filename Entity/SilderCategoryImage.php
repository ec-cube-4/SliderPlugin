<?php

namespace Plugin\SliderPlugin4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SilderCategoryImage.
 *
 * @ORM\Table(name="plg_slider_category_image")
 * @ORM\Entity(repositoryClass="Plugin\SliderPlugin4\Repository\SilderCategoryImageRepository")
 */
class SilderCategoryImage
{
    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getFileName();
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255)
     */
    private $file_name;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="smallint", options={"unsigned":true})
     */
    private $sort_no;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \Eccube\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Category", inversedBy="SilderCategoryImage")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     */
    private $Category;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     * })
     */
    private $Creator;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fileName.
     *
     * @param string $fileName
     *
     * @return SilderCategoryImage
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Get fileName.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set sortNo.
     *
     * @param int $sortNo
     *
     * @return SilderCategoryImage
     */
    public function setSortNo($sortNo)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    /**
     * Get sortNo.
     *
     * @return int
     */
    public function getSortNo()
    {
        return $this->sort_no;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return SilderCategoryImage
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set category.
     *
     * @param \Eccube\Entity\Category|null $category
     *
     * @return SilderCategoryImage
     */
    public function setCategory(\Eccube\Entity\Category $category = null)
    {
        $this->Category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Eccube\Entity\Category|null
     */
    public function getCategory()
    {
        return $this->Category;
    }

    /**
     * Set creator.
     *
     * @param \Eccube\Entity\Member|null $creator
     *
     * @return SilderCategoryImage
     */
    public function setCreator(\Eccube\Entity\Member $creator = null)
    {
        $this->Creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getCreator()
    {
        return $this->Creator;
    }


}
