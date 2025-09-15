<?php

namespace App\Command;

use App\Entity\Devise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:insert-default-devises',
    description: 'Insère les devises par défaut dans la base de données',
)]
class InsertDefaultDevisesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $devises = [
            // Devises africaines principales
            ['XOF', 'Franc CFA Ouest-Africain'],
            ['XAF', 'Franc CFA Centrafricain'],
            ['DZD', 'Dinar Algérien'],
            ['EGP', 'Livre Égyptienne'],
            ['MAD', 'Dirham Marocain'],
            ['TND', 'Dinar Tunisien'],
            ['NGN', 'Naira Nigériane'],
            ['ZAR', 'Rand Sud-Africain'],
            ['KES', 'Shilling Kenyan'],
            ['GHS', 'Cedi Ghanéen'],
            ['ETB', 'Birr Éthiopien'],
            ['UGX', 'Shilling Ougandais'],
            ['TZS', 'Shilling Tanzanien'],
            ['MWK', 'Kwacha Malawien'],
            ['ZMW', 'Kwacha Zambien'],
            ['BWP', 'Pula Botswanais'],
            ['SLL', 'Leone Sierra-Léonais'],
            ['LRD', 'Dollar Libérien'],
            ['GMD', 'Dalasi Gambien'],
            ['CDF', 'Franc Congolais'],

            // Devises internationales principales
            ['USD', 'Dollar Américain'],
            ['EUR', 'Euro'],
            ['GBP', 'Livre Sterling'],
            ['JPY', 'Yen Japonais'],
            ['CHF', 'Franc Suisse'],
            ['CAD', 'Dollar Canadien'],
            ['AUD', 'Dollar Australien'],
            ['NZD', 'Dollar Néo-Zélandais'],
            ['SEK', 'Couronne Suédoise'],
            ['NOK', 'Couronne Norvégienne'],
            ['DKK', 'Couronne Danoise'],
            ['PLN', 'Zloty Polonais'],
            ['CZK', 'Couronne Tchèque'],
            ['HUF', 'Forint Hongrois'],
            ['RON', 'Leu Roumain'],
            ['BGN', 'Lev Bulgare'],
            ['HRK', 'Kuna Croate'],
            ['RSD', 'Dinar Serbe'],
            ['UAH', 'Hryvnia Ukrainienne'],
            ['RUB', 'Rouble Russe'],

            // Devises asiatiques
            ['CNY', 'Yuan Chinois'],
            ['INR', 'Roupie Indienne'],
            ['KRW', 'Won Sud-Coréen'],
            ['SGD', 'Dollar de Singapour'],
            ['HKD', 'Dollar de Hong Kong'],
            ['TWD', 'Dollar Taïwanais'],
            ['THB', 'Baht Thaïlandais'],
            ['MYR', 'Ringgit Malaisien'],
            ['IDR', 'Roupie Indonésienne'],
            ['PHP', 'Peso Philippin'],
            ['VND', 'Dong Vietnamien'],
            ['PKR', 'Roupie Pakistanaise'],
            ['BDT', 'Taka Bangladeshi'],
            ['LKR', 'Roupie Sri Lankaise'],
            ['NPR', 'Roupie Népalaise'],
            ['MMK', 'Kyat Birman'],
            ['KHR', 'Riel Cambodgien'],
            ['LAK', 'Kip Lao'],
            ['BND', 'Dollar de Brunei'],
            ['MOP', 'Pataca de Macao'],

            // Devises du Moyen-Orient
            ['SAR', 'Riyal Saoudien'],
            ['AED', 'Dirham des Émirats Arabes Unis'],
            ['QAR', 'Riyal Qatari'],
            ['KWD', 'Dinar Koweïtien'],
            ['BHD', 'Dinar Bahreïni'],
            ['OMR', 'Rial Omanais'],
            ['JOD', 'Dinar Jordanien'],
            ['LBP', 'Livre Libanaise'],
            ['ILS', 'Shekel Israélien'],
            ['TRY', 'Livre Turque'],
            ['IRR', 'Rial Iranien'],
            ['IQD', 'Dinar Irakien'],
            ['AFN', 'Afghani Afghan'],
            ['YER', 'Rial Yéménite'],
            ['SYP', 'Livre Syrienne'],

            // Devises des Amériques
            ['BRL', 'Real Brésilien'],
            ['ARS', 'Peso Argentin'],
            ['CLP', 'Peso Chilien'],
            ['COP', 'Peso Colombien'],
            ['PEN', 'Sol Péruvien'],
            ['UYU', 'Peso Uruguayen'],
            ['PYG', 'Guaraní Paraguayen'],
            ['BOB', 'Boliviano Bolivien'],
            ['VES', 'Bolívar Vénézuélien'],
            ['GTQ', 'Quetzal Guatémaltèque'],
            ['HNL', 'Lempira Hondurien'],
            ['NIO', 'Córdoba Nicaraguayen'],
            ['CRC', 'Colón Costaricain'],
            ['PAB', 'Balboa Panaméen'],
            ['DOP', 'Peso Dominicain'],
            ['JMD', 'Dollar Jamaïcain'],
            ['TTD', 'Dollar de Trinité-et-Tobago'],
            ['BBD', 'Dollar Barbadien'],
            ['XCD', 'Dollar des Caraïbes Orientales'],

            // Devises océaniennes
            ['FJD', 'Dollar Fidjien'],
            ['PGK', 'Kina Papouasie-Nouvelle-Guinée'],
            ['WST', 'Tala Samoan'],
            ['TOP', 'Pa\'anga Tongan'],
            ['VUV', 'Vatu Vanuatu'],
            ['SBD', 'Dollar des Îles Salomon'],

            // Cryptomonnaies populaires
            ['BTC', 'Bitcoin'],
            ['ETH', 'Ethereum'],
            ['USDT', 'Tether'],
            ['BNB', 'Binance Coin'],
            ['ADA', 'Cardano'],
            ['SOL', 'Solana'],
            ['XRP', 'Ripple'],
            ['DOT', 'Polkadot'],
            ['DOGE', 'Dogecoin'],
            ['AVAX', 'Avalanche'],

            // Devises spéciales
            ['XDR', 'Droits de Tirage Spéciaux (FMI)'],
            ['XAG', 'Argent (once troy)'],
            ['XAU', 'Or (once troy)'],
            ['XPD', 'Palladium (once troy)'],
            ['XPT', 'Platine (once troy)'],
        ];

        $io->title('Insertion des devises par défaut');

        $count = 0;
        $skipped = 0;

        foreach ($devises as [$code, $nom]) {
            // Vérifier si la devise existe déjà
            $existingDevise = $this->entityManager->getRepository(Devise::class)->findOneBy(['code' => $code]);
            
            if ($existingDevise) {
                $skipped++;
                $io->text("⏭️  Devise {$code} déjà existante - ignorée");
                continue;
            }

            $devise = new Devise();
            $devise->setCode($code);
            $devise->setNom($nom);

            $this->entityManager->persist($devise);
            $count++;

            $io->text("✅ Devise {$code} ({$nom}) ajoutée");
        }

        $this->entityManager->flush();

        $io->success([
            "Insertion terminée !",
            "📊 Statistiques :",
            "   • {$count} devises ajoutées",
            "   • {$skipped} devises ignorées (déjà existantes)",
            "   • Total : " . ($count + $skipped) . " devises traitées"
        ]);

        // Afficher quelques devises populaires
        $popularDevises = $this->entityManager->getRepository(Devise::class)->findBy([
            'code' => ['XOF', 'USD', 'EUR', 'GBP', 'JPY']
        ]);

        $io->section('Devises populaires disponibles :');
        foreach ($popularDevises as $devise) {
            $io->text("• {$devise->getCode()} - {$devise->getNom()}");
        }

        return Command::SUCCESS;
    }
}
