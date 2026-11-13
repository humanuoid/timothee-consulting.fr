<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Page;
use App\Domain\Entity\PageContent;
use App\Domain\Entity\PageModel;
use App\Domain\Entity\User;
use App\Domain\I18n\Locales;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser('admin@timothee-consulting.fr', 'admin1234', ['ROLE_ADMIN'], 'Emmanuel');
        $editor = $this->createUser('editor@timothee-consulting.fr', 'editor1234', ['ROLE_EDITOR'], 'Timothée');
        $manager->persist($admin);
        $manager->persist($editor);

        $model = PageModel::create('One-page', 'one-page', [
            'header',
            'hero',
            'parcours',
            'competences',
            'projets',
            'contact'
        ]);
        $manager->persist($model);

        $page = Page::create($model, $admin);
        $manager->persist($page);

        // draft starts empty: the component payloads arrive with the
        // editor (phases 4/6) and will enrich this fixture
        $french = PageContent::create(
            $page,
            Locales::FR,
            'accueil',
            'Accueil',
            'Accueil',
            'Portfolio de Timothée Consulting'
        );
        $manager->persist($french);

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(
        string $email,
        #[\SensitiveParameter]
        string $plainPassword,
        array $roles,
        string $fullName
    ): User {
        $hash = $this->passwordHasher->hashPassword(new PlainPasswordUser($plainPassword), $plainPassword);

        return User::create($email, $hash, $roles, $fullName, Locales::FR);
    }
}
