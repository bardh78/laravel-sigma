#!/usr/bin/env bash
git add .
git commit -m "updates-$(date +'%Y-%m-%d-%H-%M-%S')"
git push
