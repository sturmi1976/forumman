<?php

namespace Lanius\Forumman\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use Lanius\Forumman\Domain\Repository\PostsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Lanius\Forumman\Domain\Repository\GroupRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use Lanius\Forumman\Domain\Repository\FrontendUserRepository;
use Lanius\Forumman\Domain\Model\Group;


final class FrontendUser extends AbstractEntity
{
    protected ObjectStorage $usergroup;

    public function __construct()
    {
        $this->usergroup = new ObjectStorage();
    }

    protected string $username = '';
    protected string $name = '';
    protected string $email = '';
    protected string $company = '';
    protected string $www = '';
    protected string $profilbeschreibung = '';
    protected string $signature = '';
    protected ?string $slug = null;
    protected ?string $birthday = '';
    protected ?int $age = 0;
    protected int $isOnline = 0;
    protected int $nowonline = 0;
    protected int $age2 = 0;

    public function getAge2(): int
    {
        return $this->age2;
    }

    public function setAge2(int $age2): void
    {
        $this->age2 = $age2;
    }


    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    public function getProfilbeschreibung(): string
    {
        return $this->profilbeschreibung;
    }

    public function setProfilbeschreibung(string $profilbeschreibung): void
    {
        $this->profilbeschreibung = $profilbeschreibung;
    }

    public function getWww(): string
    {
        return $this->www;
    }

    public function setWww(string $www): void
    {
        $this->www = $www;
    }

    public function getIsOnline(): int
    {
        return $this->isOnline;
    }

    public function setIsOnline(): void
    {
        $this->isOnline = 0;

        $uid = $this->getUid();
        if (!$uid) {
            return;
        }

        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $result = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid))
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($result && isset($result['is_online'])) {
            $this->isOnline = (int)$result['is_online'];
        }
    }




    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference|null
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ?FileReference $image = null;

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }



    /*
    public function setUsergroup(?string $usergroup): void
    {
        $this->usergroup = $usergroup;
    }*/

    /**
     * @return ObjectStorage<Group>
     */
    public function getUsergroup(): ObjectStorage
    {
        return $this->usergroup;
    }


    public function initializeObject(): void
    {
        // Slug automatisch generieren, falls noch leer
        if (!$this->slug) {
            $this->slug = $this->generateSlug($this->username);
        }
    }


    /**
     * Virtuelle Property: Anzahl der Posts
     */
    public function getPostCount(): int
    {
        /** @var PostsRepository $postsRepository */
        $postsRepository = GeneralUtility::makeInstance(PostsRepository::class);

        return $postsRepository->countByUser($this->getUid());
    }



    public function getBirthday(): string
    {
        return $this->birthday;
    }

    public function setBirthday(string $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }



    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): void
    {
        $this->company = $company;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
        if (!$this->slug) {
            $this->slug = $this->generateSlug($username);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }


    protected function generateSlug(string $string): string
    {
        $slug = mb_strtolower($string);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'user-' . time();
    }
}
