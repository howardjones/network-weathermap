// Taken almost verbatim from https://gist.github.com/bfocht/a0def4432d0f93dc28bc7cf64ebe8be1

// copy the just-built js code into the place weathermap/cacti expect to find it

const fs = require('fs');
const src = './build/';
const dest = '../../cacti-resources/user/';

const targets = ["main.js", "main.css"];


//const target = "main.js";

for (let target of targets) {

    let file = require('./build/asset-manifest.json')[target];
    console.log(`${src}${file} => ${dest}${target}`);

    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest);
    }

    if (fs.existsSync(dest + target)) {
        fs.unlinkSync(dest + target);
    }

    // remove source maps from production and move file to dist folder
    fs.readFile(src + file, 'utf8', (err, data) => {
        if (err) {
            console.log('Unable to read file from manifest.');
            process.exit(1);
        }

        // let version = `/*!\n* weathermap-user-plugin v${process.env.npm_package_version}\n* Licensed under MIT\n* \n*/\n`;
        // let result = data.split('//# sourceMappingURL');

        // if (result[result.length - 1] !== undefined && result.length > 1) {
        //     fs.writeFileSync(dest + target, version);
        fs.writeFileSync(dest + target, data);
        // }
    });
}