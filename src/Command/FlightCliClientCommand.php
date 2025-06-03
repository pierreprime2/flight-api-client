<?php

namespace App\Command;

use App\Service\AviationStackClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function PHPSTORM_META\type;

#[AsCommand(
    name: 'app:flights',
    description: 'Add a short description for your command',
)]
class FlightCliClientCommand extends Command
{
    private AviationStackClient $apiClient;

    public function __construct(AviationStackClient $apiClient)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'Query type: flight | departure | destination')
            ->addArgument('code', InputArgument::REQUIRED, 'Airport code or flight ID')
            ->addArgument('limit', InputArgument::OPTIONAL, 'Number of results to show (only for departure/destination)', 5)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // type casts
        $type = strtolower($input->getArgument('type'));
        $code = strtoupper($input->getArgument('code'));
        $limit = (int) $input->getArgument('limit');

        try {
            switch ($type) {
                case 'departure':
                    $flights = $this->apiClient->getDeparturesFromAirport($code, $limit);
                    print_r($flights);
                    $this->renderFlights($flights, $io);
                    break;
                case 'destination':
                    $flights = $this->apiClient->getArrivals($code, $limit);
                    $this->renderFlights($flights, $io);
                    break;
                case 'flight':
                    $flight = $this->apiClient->getFlightDetails($code);
                    if ($flight) {
                        $io->title("Flight $code Details");
                        $io->listing($this->formatFlightDetails($flight));
                    } else {
                        $io->warning("No detail found for flight ID: $code");
                    }
                    break;
                default:
                    $io->error('Invalid type. Must be one of: flight, departure, destination');
                    return Command::INVALID;
            }
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function renderFlights(array $flights, SymfonyStyle $io): void
    {
        if (empty($flights)) {
            $io->warning("No flights found.");
            return;
        }

        $row = [];
        foreach ($flights as $flight) {
            $rows[] = [
                $flight['flight']['iata'] ?? 'N/A',
                $flight['airline']['name'] ?? 'N/A',
                $flight['departure']['airport'] ?? 'N/A',
                $this->formatTime($flight['departure']['scheduled'] ?? null),
                $flight['arrival']['airport'] ?? 'N/A',
                $this->formatTime($flight['arrival']['scheduled'] ?? null),
                $flight['flight_status'] ?? 'N/A',
            ];
        }

        $io->table(
            ['Flight #', 'Airline', 'From', 'Dep Time', 'To', 'Arr Time', 'Status'],
            $rows
        );
    }

    private function formatTime(?string $time): string
    {
        if (!$time)
            return 'N/A';
        try {
            return (new \DateTime($time))->format('Y-m-d H:i');
        } catch (\Exception) {
            return 'Invalid';
        }
    }

    private function formatFlight(array $flight): array
    {
        return [
            'Flight #: ' . ($flight['flight_number'] ?? 'N/A'),
            'Airline: ' . ($flight['airline']['name'] ?? 'N/A'),
            'From: ' . ($flight['departure_airport']['name'] ?? 'N/A'),
            'To: ' . ($flight['arrival_airport']['name'] ?? 'N/A'),
            'Duration: ' . ($flight['duration'] ?? 'N/A'),
        ];
    }

    private function formatFlightDetails(array $flight): array
    {
        return [
            "Flight #: " . ($flight['flight']['iata'] ?? 'N/A'),
            "Airline: " . ($flight['airline']['name'] ?? 'N/A'),
            "From: " . ($flight['departure']['airport'] ?? 'N/A'),
            "Dep Time: " . $this->formatTime($flight['departure']['scheduled'] ?? null),
            "To: " . ($flight['arrival']['airport'] ?? 'N/A'),
            "Arr Time: " . $this->formatTime($flight['arrival']['scheduled'] ?? null),
            "Status: " . ($flight['status'] ?? 'N/A'),
        ];
    }
}
