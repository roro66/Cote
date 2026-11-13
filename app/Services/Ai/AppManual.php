<?php

namespace App\Services\Ai;

use App\Models\User;

/**
 * Busca en resources/manual/MANUAL.md. Cada sección ## declara en «**Quién puede:**» los roles
 * que la abren; sin roles es para todos. Solo se devuelven secciones que el usuario puede abrir.
 */
class AppManual
{
    public const PATH = 'resources/manual/MANUAL.md';

    private const STOP_WORDS = [
        'como', 'para', 'que', 'una', 'uno', 'unos', 'unas', 'los', 'las', 'del', 'con', 'por', 'app',
        'uso', 'usar', 'hace', 'hacer', 'hago', 'donde', 'esta', 'esto', 'este', 'sobre', 'desde', 'cuando',
        'cual', 'puede', 'puedo', 'debo', 'tengo', 'quiero', 'necesito', 'cote', 'sistema', 'pantalla',
    ];

    /**
     * @return array{ok: bool, sin_acceso?: bool, mensaje?: string, secciones?: list<array{titulo: string, texto: string}>}
     */
    public function search(string $query, ?User $user): array
    {
        $tokens = $this->tokens($query);
        if ($tokens === []) {
            return ['ok' => false, 'mensaje' => 'Indica el tema: por ejemplo rendición, entrega, cierre de mes o contraseña.'];
        }

        $ranked = [];
        foreach ($this->sections() as $section) {
            $score = $this->score($tokens, $section['title_folded'], $section['body_folded']);
            if ($score > 0) {
                $ranked[] = ['score' => $score, 'section' => $section];
            }
        }

        if ($ranked === []) {
            return ['ok' => false, 'mensaje' => 'El manual no tiene un procedimiento para ese tema.'];
        }

        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

        $allowed = array_values(array_filter($ranked, fn ($row) => $this->allows($user, $row['section'])));

        if ($allowed === [] || ! $this->allows($user, $ranked[0]['section']) && $allowed[0]['score'] < $ranked[0]['score'] / 2) {
            $title = $ranked[0]['section']['title'];

            return [
                'ok' => true,
                'sin_acceso' => true,
                'mensaje' => "El usuario no tiene acceso a «{$title}». No describas los pasos. Dile que esa pantalla corresponde al Jefe o al Tesorero.",
            ];
        }

        $secciones = [];
        $budget = 7000;
        foreach (array_slice($allowed, 0, 2) as $row) {
            $text = mb_substr($row['section']['body'], 0, min(4000, $budget));
            $budget -= mb_strlen($text);
            $secciones[] = ['titulo' => $row['section']['title'], 'texto' => $text];
            if ($budget <= 0) {
                break;
            }
        }

        return ['ok' => true, 'sin_acceso' => false, 'secciones' => $secciones];
    }

    /**
     * @return list<array{title: string, title_folded: string, body: string, body_folded: string, roles: list<string>}>
     */
    private function sections(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $path = base_path(self::PATH);
        $markdown = is_file($path) ? (string) file_get_contents($path) : '';
        preg_match_all('/^(#{1,2}) (.+)$/m', $markdown, $matches, PREG_OFFSET_CAPTURE);

        $sections = [];
        foreach ($matches[0] as $index => $match) {
            $start = $match[1] + strlen($match[0]);
            $end = $matches[0][$index + 1][1] ?? strlen($markdown);
            $body = trim(substr($markdown, $start, $end - $start));
            if ($body === '') {
                continue;
            }
            $title = trim($matches[2][$index][0]);
            $sections[] = [
                'title' => $title,
                'title_folded' => $this->fold($title),
                'body' => $body,
                'body_folded' => $this->fold($body),
                'roles' => $this->roles($body),
            ];
        }

        return $cache = $sections;
    }

    /** @return list<string> */
    private function roles(string $body): array
    {
        if (! preg_match('/\*\*Quién puede:\*\*(.+)/u', $body, $line)) {
            return [];
        }
        preg_match_all('/rol `([^`]+)`/u', $line[1], $roles);

        return $roles[1];
    }

    /** @param  array{roles: list<string>}  $section */
    private function allows(?User $user, array $section): bool
    {
        if ($section['roles'] === []) {
            return true;
        }

        return $user !== null && $user->hasAnyRole($section['roles']);
    }

    /** @return list<string> */
    private function tokens(string $query): array
    {
        $parts = preg_split('/[^a-z0-9]+/', $this->fold($query)) ?: [];

        return array_values(array_unique(array_map(
            fn ($token) => $this->stem($token),
            array_filter($parts, fn ($token) => strlen($token) >= 4 && ! in_array($token, self::STOP_WORDS, true))
        )));
    }

    /** Raíz corta para que «rendición», «rendiciones» y «rendir» coincidan. */
    private function stem(string $token): string
    {
        return strlen($token) > 6 ? substr($token, 0, 6) : rtrim($token, 's');
    }

    /** @param  list<string>  $tokens */
    private function score(array $tokens, string $title, string $body): int
    {
        $score = 0;
        foreach ($tokens as $token) {
            if (str_contains($title, $token)) {
                $score += 5;
            } elseif (str_contains($body, $token)) {
                $score += 1;
            }
        }

        return $score;
    }

    private function fold(string $text): string
    {
        return strtr(mb_strtolower($text), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
