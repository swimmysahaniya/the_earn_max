# Generated by Django 5.1.6 on 2025-03-11 12:44

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('myapp', '0012_extraincome'),
    ]

    operations = [
        migrations.AlterField(
            model_name='users',
            name='mobile',
            field=models.CharField(db_collation='utf8mb4_general_ci', max_length=15, unique=True),
        ),
    ]
