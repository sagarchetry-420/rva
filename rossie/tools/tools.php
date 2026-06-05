<?php
/**
 * Gemini Tool Definitions
 */
return [
    [
        "functionDeclarations" => [
            [
                "name" => "create_quote",
                "description" => "Draft a new quote to be displayed on the school website homepage. Only use this if the user explicitly asks to add or post a new quote.",
                "parameters" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "quote_text" => [
                            "type" => "STRING",
                            "description" => "The content of the quote."
                        ],
                        "author" => [
                            "type" => "STRING",
                            "description" => "The author of the quote. Only include letters, spaces, dots, and hyphens."
                        ]
                    ],
                    "required" => ["quote_text", "author"]
                ]
            ]
        ]
    ]
];
