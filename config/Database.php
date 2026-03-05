<?php
/**
 * config/Database.php
 *
 * Responsável por estabelecer e fornecer a conexão PDO com o banco de dados.
 * Implementa o padrão Singleton para evitar múltiplas conexões desnecessárias.
 */

declare(strict_types=1);

class Database
{
    // ── Configurações de conexão ─────────────────────────────────────────────
    private const HOST    = 'localhost';
    private const DBNAME  = 'capitaoMuzzarela_db';
    private const USER    = 'root';       // ← altere para o usuário do seu banco
    private const PASS    = 'root';           // ← altere para a senha do seu banco
    private const CHARSET = 'utf8mb4';

    /** @var Database|null Instância única (Singleton) */
    private static ?Database $instance = null;

    /** @var PDO Objeto de conexão PDO */
    private PDO $pdo;

    // ── Construtor privado (Singleton) ────────────────────────────────────────
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::HOST,
            self::DBNAME,
            self::CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lança exceções em erros
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // retorna arrays associativos
            PDO::ATTR_EMULATE_PREPARES   => false,                     // prepared statements reais
        ];

        try {
            $this->pdo = new PDO($dsn, self::USER, self::PASS, $options);
        } catch (PDOException $e) {
            // Em produção, nunca exponha detalhes técnicos ao usuário
            error_log('[Database] Falha na conexão: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível conectar ao banco de dados.');
        }
    }

    /**
     * Retorna a instância única de Database (Singleton).
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna o objeto PDO para uso nos Models.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // ── Bloqueia clone e deserialização para manter o Singleton ──────────────
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize a singleton.');
    }
}
