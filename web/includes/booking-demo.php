<?php
/**
 * ============================================================================
 *  UKÁŽKOVÉ (DEMO) REZERVÁCIE – NEJDE O SKUTOČNÉ OBSADENIE MASÉRKY
 * ============================================================================
 *
 * Tento súbor slúži IBA na prezentáciu návrhu webu. „Rezervácie“ sa vypočítajú
 * z dátumu (vždy rovnako, aby sa ukážka pri obnovení stránky nemenila) a nemajú
 * žiadnu súvislosť so skutočnými objednávkami. Neobsahujú žiadne osobné údaje.
 *
 * Tvar údajov zodpovedá budúcim skutočným rezerváciám: začiatok + dĺžka v minútach.
 * Po schválení webu: booking_reservations() v includes/booking.php napojiť
 * na skutočnú evidenciu rezervácií a tento súbor odstrániť.
 */
declare(strict_types=1);

/**
 * Ukážkové rezervácie pre daný deň – iba v rámci (ukážkového) pracovného času a s prestávkou medzi klientmi.
 * Približne každý 7. pracovný deň je obsadený celý; inak niekoľko rezervácií s dĺžkami z ponuky (30 – 90 min).
 *
 * @return list<array{start: string, minutes: int}>
 */
function booking_demo_reservations(DateTimeImmutable $day): array
{
    $open = booking_open_intervals($day);
    if (!$open) {
        return [];
    }
    $key    = $day->format('Y-m-d');
    $step   = booking_time_step();
    $buffer = booking_buffer_minutes();

    if (crc32('mvs-demo-day|' . $key) % 7 === 0) {
        return array_map(
            static fn (array $i): array => ['start' => booking_from_minutes($i[0]), 'minutes' => $i[1] - $i[0]],
            $open
        );
    }

    $lengths      = [30, 40, 50, 60, 90];
    $reservations = [];
    foreach ($open as [$from, $to]) {
        $t = $from;
        while ($t + $lengths[0] <= $to) {
            $hash = crc32('mvs-demo-slot|' . $key . '|' . $t);
            $len  = $lengths[intdiv($hash, 100) % count($lengths)];
            if ($hash % 100 < 14 && $t + $len <= $to) {
                $reservations[] = ['start' => booking_from_minutes($t), 'minutes' => $len];
                // ďalšia ukážková rezervácia najskôr po prestávke, zarovnaná na krok kalendára
                $t = $from + (int) ceil(($t + $len + $buffer - $from) / $step) * $step;
                continue;
            }
            $t += $step;
        }
    }
    return $reservations;
}
