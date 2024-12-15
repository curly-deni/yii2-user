<?php

namespace aesis\user\traits;

trait ProfileFind
{
    /**
     * Поиск пользователей с профилями по строке запроса.
     *
     * @param string $searchQuery Строка поиска.
     * @return \yii\db\ActiveQuery ActiveQuery, который можно продолжать для получения результатов.
     */
    public static function search($searchQuery, $query = null)
    {
        if ($query === null) {
            $query = self::find();
        }

        // Если строка поиска пустая, возвращаем все профили
        if (empty($searchQuery)) {
            return $query;
        }

        // Приводим строку поиска к нижнему регистру
        $searchQuery = strtolower($searchQuery);

        // Соединяем с пользователем и фильтруем по id или имени
        $query->joinWith(['user u']) // Соединяем с таблицей пользователей, если связь настроена
        ->andWhere(['or',
            ['like', 'LOWER(u.username)', $searchQuery], // Поиск по имени пользователя
            ['like', 'LOWER(u.email)', $searchQuery], // Поиск по email
            ['like', 'LOWER(profile.name)', $searchQuery], // Поиск по имени профиля
            ['like', 'LOWER(profile.surname)', $searchQuery] // Поиск по фамилии профиля
        ]);

        return $query;
    }
}