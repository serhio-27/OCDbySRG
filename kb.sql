-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 14 2025 г., 07:16
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `kb`
--

-- --------------------------------------------------------

--
-- Структура таблицы `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `complaint` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `complaint`, `created_at`) VALUES
(2, 6, 5, '2025-02-01', '09:00:00', 'confirmed', 'кашель', '2025-01-30 09:47:53'),
(3, 6, 5, '2025-02-05', '10:00:00', 'confirmed', 'температура', '2025-02-05 10:56:59'),
(9, 6, 10, '2025-05-14', '14:30:00', 'confirmed', 'ппп', '2025-05-12 08:35:57');

-- --------------------------------------------------------

--
-- Структура таблицы `consultation_messages`
--

CREATE TABLE `consultation_messages` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `file_original_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `consultation_messages`
--

INSERT INTO `consultation_messages` (`id`, `appointment_id`, `sender_id`, `message`, `created_at`, `file_path`, `file_original_name`, `file_type`) VALUES
(1, 2, 5, 'Здравствуйте, что вас беспокоит ?', '2025-02-05 11:19:27', NULL, NULL, NULL),
(2, 2, 6, 'здравствуйте, кашель и температура', '2025-02-05 11:26:43', NULL, NULL, NULL),
(18, 2, 6, '', '2025-05-12 07:40:45', 'uploads/consultation_files/6821a5fddee5a_NOh9ND7aHbM.jpg', NULL, NULL),
(19, 9, 6, '/', '2025-05-12 09:04:18', NULL, NULL, NULL),
(20, 3, 5, 'п', '2025-05-14 04:56:16', NULL, NULL, NULL),
(21, 2, 5, 'пп', '2025-05-14 04:56:26', NULL, NULL, NULL),
(22, 2, 5, 'п', '2025-05-14 04:56:29', NULL, NULL, NULL),
(23, 2, 5, 'п', '2025-05-14 04:57:40', NULL, NULL, NULL),
(24, 2, 6, 'при', '2025-05-14 05:06:38', NULL, NULL, NULL),
(25, 2, 6, 'ппп', '2025-05-14 05:09:21', NULL, NULL, NULL),
(26, 2, 6, 'ппп', '2025-05-14 05:09:23', NULL, NULL, NULL),
(27, 2, 6, 'ппп', '2025-05-14 05:09:24', NULL, NULL, NULL),
(28, 2, 5, 'и', '2025-05-14 05:10:00', NULL, NULL, NULL),
(29, 2, 5, 'п', '2025-05-14 05:16:12', NULL, NULL, NULL),
(30, 2, 5, '', '2025-05-14 05:16:18', 'uploads/consultation_files/6824272227dae_1747199778.pdf', 'T100CH154-M10.pdf', 'application/pdf');

-- --------------------------------------------------------

--
-- Структура таблицы `personal_documents`
--

CREATE TABLE `personal_documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `passport_series` varchar(4) DEFAULT NULL,
  `passport_number` varchar(6) DEFAULT NULL,
  `passport_issued_by` varchar(256) DEFAULT NULL,
  `passport_issue_date` date DEFAULT NULL,
  `snils` varchar(14) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `oms_number` int(16) NOT NULL,
  `oms_insurance_company` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `personal_documents`
--

INSERT INTO `personal_documents` (`id`, `user_id`, `passport_series`, `passport_number`, `passport_issued_by`, `passport_issue_date`, `snils`, `created_at`, `updated_at`, `oms_number`, `oms_insurance_company`) VALUES
(1, 1, '5000', '123456', 'ГУ МВД России по Новосибирской области', '2015-05-15', '123-456-789 01', '2025-01-26 08:35:51', '2025-01-26 08:35:51', 0, ''),
(3, 3, '5002', '111222', 'ГУ МВД России по Новосибирской области', '2014-03-10', '111-222-333 03', '2025-01-26 08:35:51', '2025-01-26 08:35:51', 0, ''),
(4, 4, '5003', '333444', 'ГУ МВД России по Новосибирской области', '2018-09-25', '444-555-666 04', '2025-01-26 08:35:51', '2025-01-26 08:35:51', 0, ''),
(5, 5, '5006', '554511', 'ГУ МВД России по Новосибирской области', '2006-06-07', '141-398-038 56', '2025-01-26 08:35:51', '2025-01-26 08:52:33', 0, ''),
(6, 6, '5006', '777888', 'ГУ МВД России по Новосибирской области', '2019-01-15', '123-987-456 06', '2025-01-26 08:35:51', '2025-04-08 13:33:56', 0, 'альфа');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `surname` varchar(256) NOT NULL,
  `patronymic` varchar(256) DEFAULT NULL,
  `email` varchar(256) NOT NULL,
  `phone` varchar(256) NOT NULL,
  `password` varchar(256) NOT NULL,
  `agree` tinyint(1) NOT NULL DEFAULT 0,
  `api_token` varchar(256) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'patient',
  `specialization` varchar(256) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `surname`, `patronymic`, `email`, `phone`, `password`, `agree`, `api_token`, `type`, `specialization`, `created_at`) VALUES
(1, 'Иван', 'Иванов', 'Иванович', 'ivan@mail.ru', '+79001234567', '$2y$10$WW8dDFZmHllrP2EvaW6/8.XO9GZQi43CnPFhtOZITTae/p8q4AtUG', 1, NULL, 'patient', NULL, '2024-03-14 10:00:00'),
(3, 'Админ', 'Админов', 'Админович', 'admin@mail.ru', '+79009876543', '$2y$10$WW8dDFZmHllrP2EvaW6/8.XO9GZQi43CnPFhtOZITTae/p8q4AtUG', 1, NULL, 'admin', NULL, '2024-03-14 10:00:00'),
(4, 'Кирилл', 'Иванченко', 'Федорович', 'IKF@gmail.com', '+7 (800) 555-35-44', '$2y$10$rCFYsYzuV54hwPwgDFxmdOBkKA6nLsjYNnGOwmc6ZUBLDw3VTX80e', 1, NULL, 'patient', NULL, '2025-01-17 13:40:39'),
(5, 'Галина', 'Пискарева', 'Артемовна', 'PGA@gmail.com', '+7 (933) 444-55-66', '$2y$10$zOhfV4ljLkxHIA.dnk3MkekwDVN9/uSVKk8qixJ8UDyU8I3GuZU9K', 1, 'fffbeb78f4c0d62301cb289ccc1e0946e8a6d760fc540b7e8d967d6fb849e3a0', 'doctor', 'Офтальмолог', '2025-01-17 13:59:20'),
(6, 'Николай', 'Николаев', NULL, 'NNN@gmail.com', '+7 (555) 557-73', '$2y$10$Cu.mJAd5AxGuL66nMM756eswhtT/JUyxt4sa/zTNDWDOvPFOiDer2', 1, NULL, 'patient', NULL, '2025-01-21 10:48:36'),
(7, 'Наталья', 'Погорелова', 'Алексеевна', 'PNA@mail.ru', '8(38355)60-025', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Терапевт', '2025-03-31 12:43:36'),
(8, 'Айсулу', 'Абетова', 'Камариденовна', 'AAK@mail.ru', '8(38355)50-444', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Терапевт', '2025-03-31 12:43:36'),
(9, 'Виктория', 'Батурина', 'Викторовна', 'BVV@mail.ru', '8(38355)21-511', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Терапевт', '2025-03-31 12:43:36'),
(10, 'Елена', 'Ломакина', 'Николаевна', 'LEN@mail.ru', '8(38355)32-215', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Гинеколог', '2025-03-31 12:43:36'),
(11, 'Холикджон', 'Умаров', 'Орифович', 'UHO@mail.ru', '8(38355)32-421', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Хирург', '2025-03-31 12:43:36'),
(12, 'Александр', 'Лидер', 'Владимирович', 'LAV@mail.ru', '8(38355)52-755', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Хирург', '2025-03-31 12:43:36'),
(13, 'Нина', 'Лёвкина', 'Николаевна', 'LNN@mail.ru', '8(38355)92-125', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Психолог', '2025-03-31 12:43:36'),
(14, 'Татьяна', 'Трахименок', 'Николаевна', 'TTN@mail.ru', '8(38355)42-685', '$2y$10$7iF069YACtsfx/DbAny7pOgAo7sQtY1nPBN4VM6Je7wn/MepJMsJ6', 1, NULL, 'doctor', 'Педиатр', '2025-03-31 12:43:36');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Индексы таблицы `consultation_messages`
--
ALTER TABLE `consultation_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Индексы таблицы `personal_documents`
--
ALTER TABLE `personal_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `snils` (`snils`),
  ADD UNIQUE KEY `passport_unique` (`passport_series`,`passport_number`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `consultation_messages`
--
ALTER TABLE `consultation_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT для таблицы `personal_documents`
--
ALTER TABLE `personal_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `consultation_messages`
--
ALTER TABLE `consultation_messages`
  ADD CONSTRAINT `consultation_messages_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `consultation_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `personal_documents`
--
ALTER TABLE `personal_documents`
  ADD CONSTRAINT `personal_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
