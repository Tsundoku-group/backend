<?php

namespace App\Enum;

enum ChallengeContentTypeEnum: string
{
    case BOOK = 'book';
    case PAGE = 'page';
    case CHAPTER = 'chapter';
    case ARTICLE = 'article';
    case BOOK_REVIEW = 'book_review';
    case BOOK_DESCRIPTION = 'book_description';
}
