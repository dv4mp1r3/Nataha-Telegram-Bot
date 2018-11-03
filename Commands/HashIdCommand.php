<?php

namespace Commands;


class HashIdCommand implements ICommand
{
    const MESSAGE_HASHID_NOTHING = 'Не осилил';
    const ARGS_INDEX_HASH = 0;

    /**
     * @param $args
     * @param array $decodedInput
     * @return string
     */
    public function run($args, $decodedInput = null)
    {
        if (!is_array($args))
        {
            throw new \InvalidArgumentException('$payload is not array');
        }

        $hash = $args[self::ARGS_INDEX_HASH];
        $count = 0;
        $messageText = '';
        $fileContent = file_get_contents(__DIR__ . '/../hash_prototypes.json');

        if (!is_string($fileContent))
        {
            throw new \InvalidArgumentException('$fileContent is not string');
        }

        $hashGroupsData = json_decode($fileContent, true);
        if (json_last_error() > 0)
        {
            throw new \BadFunctionCallException("json_decode error: ".json_last_error_msg());
        }

        foreach($hashGroupsData as &$group)
        {
            $regex = "/{$group['regex']}/";
            if (preg_match($regex, $hash))
            {
                $messageText .= $this->addHashesNames($group['modes']);
                $count++;
            }
        }

        if ($count > 0)
        {
            return $messageText;
        }
        else
        {
            return self::MESSAGE_HASHID_NOTHING."\"$hash\"";
        }
    }

    /**
     * @param array $data
     * @return string
     */
    protected function addHashesNames($data)
    {
        $result = '';
        foreach ($data as &$hash)
        {
            $result .= "{$hash['name']}\n";
        }
        return $result;
    }
}